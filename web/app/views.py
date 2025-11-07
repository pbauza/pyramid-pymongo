"""
Pyramid views: form, list, edit and admin handlers.
"""
import csv
from io import StringIO
import os
import json
from pyramid.view import view_config
from pyramid.response import Response
from pyramid.httpexceptions import HTTPFound, HTTPForbidden
from .models import (
    create_submission,
    list_submissions,
    get_submission,
    update_submission,
    get_collection
)

# --- Helpers ---
def get_admin_nius():
    """Return list of NIUs that are admins, from env var ADMIN_NIUS"""
    raw = os.getenv("ADMIN_NIUS", "")
    return [x.strip() for x in raw.split(",") if x.strip()]


def is_admin(niu: str) -> bool:
    """Check if the NIU is in admin list"""
    return niu and niu in get_admin_nius()


# --- Views ---

@view_config(route_name="home_slash", renderer="templates/form.jinja2")
@view_config(route_name="home", renderer="templates/form.jinja2")
def home(request):
    """
    Render the submission form and handle POST to create a new record.
    Includes NIU from CAS authentication via Nginx.
    """
    niu = request.environ.get("REMOTE_USER", None)
    context = {
        "error": None,
        "success": None,
        "values": {},
        "niu": niu,
    }

    if request.method == "POST":
        params = {k: request.params.get(k) for k in request.params.keys()}
        # Asegura que el NIU autenticado prevalezca
        if niu:
            params["niu"] = niu

        try:
            _id = create_submission(params)
            # raise alert success in html template
            context["success"] = f"Submission created successfully with ID {_id}."
        except Exception as e:
            context["error"] = str(e)
            context["values"] = params

    return context


@view_config(route_name="list", renderer="app:templates/list.jinja2")
def submissions_list(request):
    niu = request.environ.get("REMOTE_USER")
    admin_nius = os.getenv("ADMIN_NIUS", "").split(",")

    is_admin = niu in admin_nius

    # Admins see all submissions; others only their own
    if is_admin:
        docs_raw = list_submissions(limit=200)
    else:
        docs_raw = list_submissions(niu=niu, limit=200)

    docs = []
    for d in docs_raw:
        d["id"] = str(d["_id"])
        docs.append(d)
    return {"docs": docs, "is_admin": is_admin}


@view_config(route_name="edit", renderer="app:templates/edit.jinja2")
def edit_submission(request):
    """
    Allow editing only if the record belongs to the authenticated NIU,
    unless the user is an admin.
    """
    niu = request.environ.get("REMOTE_USER", None)
    admin = is_admin(niu)
    oid = request.matchdict.get("_id")
    doc = get_submission(oid)

    if not doc:
        return Response("Not found", status=404)

    if not admin and doc.get("niu") != niu:
        return HTTPForbidden("You are not allowed to edit this record.")

    doc["id"] = str(doc["_id"])
    context = {"doc": doc, "error": None, "is_admin": admin}

    if request.method == "POST":
        params = {k: request.params.get(k) for k in request.params.keys()}
        ok = update_submission(oid, params)
        if ok:
            # raise alert success in html template
            context["success"] = f"Submission updated."
            return context
        context["error"] = "Update failed. Please check the input."

    return context


@view_config(route_name="admin", renderer="app:templates/list.jinja2")
def admin_view(request):
    """
    Admin-only view: list all submissions unfiltered.
    """
    niu = request.environ.get("REMOTE_USER", None)
    if not is_admin(niu):
        return HTTPForbidden("Admin access required.")

    docs_raw = list_submissions(limit=1000)
    docs = []
    for d in docs_raw:
        d["id"] = str(d["_id"])
        docs.append(d)

    return {"docs": docs, "niu": niu, "is_admin": True}


@view_config(route_name="whoami")
def whoami(request):
    info = {
        "niu": getattr(request, "niu", None),
        "cookies_seen_by_pyramid": dict(request.cookies),
        "path": request.path,
        "original_uri": request.environ.get("HTTP_X_ORIGINAL_URI"),
    }
    return Response(json.dumps(info, indent=2), content_type="application/json")


@view_config(route_name="export_csv")
def export_csv(request):
    """
    Export all submissions in CSV format.
    Accessible only to admin NIUs (configured via env var ADMIN_NIUS).
    """
    niu = request.environ.get("REMOTE_USER")
    admin_nius = os.getenv("ADMIN_NIUS", "").split(",")

    # check access
    if niu not in admin_nius:
        return Response("Forbidden", status=403)

    col = get_collection()
    docs = list(col.find({}))

    if not docs:
        return Response("No data found", status=404)

    # Get all unique keys (for flexible schema)
    all_keys = sorted(set().union(*(d.keys() for d in docs)) - {"_id"})

    # Write CSV to memory
    output = StringIO()
    writer = csv.DictWriter(output, fieldnames=["_id"] + all_keys)
    writer.writeheader()
    for d in docs:
        d = {**{k: "" for k in all_keys}, **d}  # fill missing
        d["_id"] = str(d.get("_id"))
        writer.writerow({k: d.get(k, "") for k in ["_id"] + all_keys})

    csv_bytes = output.getvalue().encode("utf-8")

    return Response(
        body=csv_bytes,
        content_type="text/csv",
        content_disposition='attachment; filename="submissions.csv"',
    )