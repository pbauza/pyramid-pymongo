"""
Pyramid views: form, list, and edit handlers.
"""
from pyramid.view import view_config
from pyramid.response import Response
from pyramid.httpexceptions import HTTPFound
from .models import (
    create_submission,
    list_submissions,
    get_submission,
    update_submission,
)
import json

@view_config(route_name="home_slash", renderer="templates/form.jinja2")
@view_config(route_name="home", renderer="templates/form.jinja2")
def home(request):
    """
    Render the submission form and handle POST to create a new record.
    Includes NIU from CAS authentication via Nginx.
    """
    niu = request.environ["REMOTE_USER"]

    context = {
        "error": None,
        "success": None,
        "values": {},
        "niu": niu,
    }

    if request.method == "POST":
        params = {k: request.params.get(k) for k in request.params.keys()}
        params["niu"] = niu

        try:
            _id = create_submission(params)
            return HTTPFound(location=request.route_url("list"))
        except Exception as e:
            context["error"] = str(e)
            context["values"] = params

    return context


@view_config(route_name="whoami")
def whoami(request):
    info = {
        "niu": getattr(request, "niu", None),
        "cookies_seen_by_pyramid": dict(request.cookies),
        "path": request.path,
        "original_uri": request.environ.get("HTTP_X_ORIGINAL_URI"),
    }
    return Response(json.dumps(info, indent=2), content_type="application/json")

@view_config(route_name="list", renderer="app:templates/list.jinja2")
def submissions_list(request):
    docs_raw = list_submissions(limit=200)
    docs = []
    for d in docs_raw:
        d["id"] = str(d["_id"])
        docs.append(d)
    return {"docs": docs}

@view_config(route_name="edit", renderer="app:templates/edit.jinja2")
def edit_submission(request):
    oid = request.matchdict.get("_id")
    doc = get_submission(oid)
    if not doc:
        return Response("Not found", status=404)
    doc["id"] = str(doc["_id"])  # add id string for template links
    context = {"doc": doc, "error": None}
    if request.method == "POST":
        params = {k: request.params.get(k) for k in request.params.keys()}
        ok = update_submission(oid, params)
        if ok:
            return HTTPFound(location=request.route_url("list"))
        context["error"] = "Update failed. Please check the input."
    return context

