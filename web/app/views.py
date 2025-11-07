"""
Pyramid views: form, list, edit and admin handlers.
"""
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
            return HTTPFound(location=request.route_url("list"))
        except Exception as e:
            context["error"] = str(e)
            context["values"] = params

    return context


@view_config(route_name="list", renderer="app:templates/list.jinja2")
def submissions_list(request):
    """
    List all submissions.
    Normal users: only their own.
    Admins: all.
    """
    niu = request.environ.get("REMOTE_USER", None)
    admin = is_admin(niu)

    docs_raw = list_submissions(limit=500, niu=None if admin else niu)
    docs = []
    for d in docs_raw:
        d["id"] = str(d["_id"])
        docs.append(d)
    return {"docs": docs, "niu": niu, "is_admin": admin}


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
            return HTTPFound(location=request.route_path("list"))
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
