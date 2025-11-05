"""
MongoDB connection helpers and data access layer.
"""
import os
from datetime import datetime
from typing import Dict, Any, Optional

from bson import ObjectId
from pymongo import MongoClient

def list_submissions_by_niu(niu: str, limit: int = 100):
    """List submissions belonging to a specific NIU."""
    col = get_collection()
    return list(col.find({"niu": niu}).sort("created_at", -1).limit(limit))


def is_owner_or_admin(niu: str, doc: Dict[str, Any]) -> bool:
    """Return True if the NIU owns the document or is in SUPERUSERS."""
    admins = [x.strip() for x in os.getenv("SUPERUSERS", "").split(",") if x.strip()]
    return doc.get("niu") == niu or niu in admins

def get_collection():
    """
    Return a MongoDB collection using environment variables.
    """
    mongo_uri = os.getenv("MONGO_URI", "mongodb://localhost:27017")
    db_name = os.getenv("MONGO_DB", "experiments")
    col_name = os.getenv("MONGO_COLLECTION", "submissions")

    client = MongoClient(mongo_uri)
    db = client[db_name]
    return db[col_name]


def normalize_payload(payload: Dict[str, Any]) -> Dict[str, Any]:
    """
    Normalize and coerce input payload from form into the expected schema.
    - Required:
        experiment_id (str), niu (str), first_name (str), last_name (str),
        result (float), uncertainty (float)
    - Optional:
        comments (str), rectifications (int; defaults to 0)
    """
    def _num(v: Optional[str]) -> Optional[float]:
        if v is None or v.strip() == "":
            return None
        return float(v.replace(",", "."))

    doc = {
        "experiment_id": (payload.get("experiment_id") or "").strip(),
        "niu": (payload.get("niu") or "").strip(),
        "first_name": (payload.get("first_name") or "").strip(),
        "last_name": (payload.get("last_name") or "").strip(),
        "result": _num(payload.get("result")),
        "uncertainty": _num(payload.get("uncertainty")),
        "rectifications": int(payload.get("rectifications") or 0),
        "comments": (payload.get("comments") or "").strip(),
    }

    return doc


def validate_required(doc: Dict[str, Any]) -> Optional[str]:
    """
    Basic required fields validation. Return an error string or None.
    """
    required = ["experiment_id", "niu", "first_name", "last_name", "result", "uncertainty"]
    missing = [k for k in required if doc.get(k) in (None, "",)]
    if missing:
        return f"Missing required fields: {', '.join(missing)}"
    return None


def create_submission(payload: Dict[str, Any]) -> ObjectId:
    """
    Insert a new submission document and return its ObjectId.
    """
    col = get_collection()
    doc = normalize_payload(payload)
    error = validate_required(doc)
    if error:
        raise ValueError(error)

    doc["rectifications"] = 0  # Ensure new records start at 0
    now = datetime.utcnow()
    doc["created_at"] = now
    doc["updated_at"] = now

    result = col.insert_one(doc)
    return result.inserted_id


def list_submissions(limit: int = 100):
    """
    List recent submissions ordered by creation date.
    """
    col = get_collection()
    return list(col.find({}).sort("created_at", -1).limit(limit))


def get_submission(oid: str) -> Optional[Dict[str, Any]]:
    """
    Fetch a single submission by ObjectId string.
    """
    col = get_collection()
    try:
        _id = ObjectId(oid)
    except Exception:
        return None
    return col.find_one({"_id": _id})


def update_submission(oid: str, payload: Dict[str, Any]) -> bool:
    """
    Update submission fields and increment rectifications counter by 1.
    """
    col = get_collection()
    try:
        _id = ObjectId(oid)
    except Exception:
        return False

    doc = normalize_payload(payload)
    # Do not allow manual override of rectifications here
    doc.pop("rectifications", None)
    doc["updated_at"] = datetime.utcnow()

    res = col.update_one(
        {"_id": _id},
        {
            "$set": {
                "experiment_id": doc["experiment_id"],
                "niu": doc["niu"],
                "first_name": doc["first_name"],
                "last_name": doc["last_name"],
                "result": doc["result"],
                "uncertainty": doc["uncertainty"],
                "comments": doc["comments"],
                "updated_at": doc["updated_at"],
            },
            "$inc": {"rectifications": 1},
        },
    )
    return res.matched_count == 1
