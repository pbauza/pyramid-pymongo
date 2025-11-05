"""
Pyramid views: form, list, and edit handlers.
"""
from pyramid.view import view_config
from pyramid.response import Response
from pyramid.httpexceptions import HTTPFound, HTTPForbidden
from .models import (
    create_submission,
    list_submissions,
    list_submissions_by_niu,
    get_submission,
    update_submission,
    is_owner_or_admin,
)
import os
import json


@view_config(route_name="home_slash", renderer="templates/form.jinja2")
@view_config(route_name="home", renderer="templates/form.jinja2")
def home(request):
    """Submission form — automatically inject NIU and name from auth."""
    niu = request.environ.get("REMOTE_USER")
    full_name = request.environ.get("REMOTE_NAME", "Unknown User")

    if not niu:
        return Response("Unauthorized", status=401)

    # Split name if possible
    parts = full_name.split(" ", 1)
    first_name = parts[0]
    last_name = parts[1] if len(parts) > 1 else ""

    context = {"error": None, "values": {}, "niu": niu, "full_name": full_name}

    if request.method == "POST":
        params = {k: request.params.get(k) for k in request.params.keys()}
        # overwrite user-provided values to prevent tampering
        params["niu"] = niu
        params["first_name"] = first_name
        params["last_name"] = last_name

        try:
            _id = create_submission(params)
            return HTTPFound(location=request.route_path("list"))
        except Exception as e:
            context["error"] = str(e)
            context["values"] = params

    return context


@view_config(route_name="whoami")
def whoami(request):
    info = {
        "niu": request.environ.get("REMOTE_USER"),
        "name": request.environ.get("REMOTE_NAME"),
        "cookies_seen_by_pyramid": dict(request.cookies),
        "path": request.path,
        "original_uri": request.environ.get("HTTP_X_ORIGINAL_URI"),
    }
    return Response(json.dumps(info, indent=2), content_type="application/json")


@view_config(route_name="list", renderer="app:templates/list.jinja2")
def submissions_list(request):
    """Show submissions filtered by NIU, unless user is admin."""
    niu = request.environ.get("REMOTE_USER")
    admins = [x.strip() for x in os.getenv("SUPERUSERS", "").split(",") if x.strip()]

    if not niu:
        return Response("Unauthorized", status=401)

    if niu in admins:
        docs_raw = list_submissions(limit=500)
    else:
        docs_raw = list_submissions_by_niu(niu, limit=200)

    docs = []
    for d in docs_raw:
        d["id"] = str(d["_id"])
        docs.append(d)

    return {"docs": docs, "niu": niu, "is_admin": niu in admins}


@view_config(route_name="edit", renderer="app:templates/edit.jinja2")
def edit_submission(request):
    """Allow editing only if you own the submission or are admin."""
    niu = request.environ.get("REMOTE_USER")
    if not niu:
        return Response("Unauthorized", status=401)

    oid = request.matchdict.get("_id")
    doc = get_submission(oid)
    if not doc:
        return Response("Not found", status=404)

    # Check permissions
    if not is_owner_or_admin(niu, doc):
        raise HTTPForbidden("You cannot edit someone else's submission.")

    doc["id"] = str(doc["_id"])
    context = {"doc": doc, "error": None, "niu": niu}

    if request.method == "POST":
        params = {k: request.params.get(k) for k in request.params.keys()}
        # Keep ownership fields intact
        params["niu"] = doc["niu"]
        params["first_name"] = doc["first_name"]
        params["last_name"] = doc["last_name"]

        ok = update_submission(oid, params)
        if ok:
            return HTTPFound(location=request.route_path("list"))
        context["error"] = "Update failed. Please check the input."

    return context
