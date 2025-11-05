"""
Pyramid application factory and setup.
"""
from pyramid.config import Configurator
from pyramid.httpexceptions import HTTPFound
from pyramid.view import view_config


def main(global_config=None, **settings):
    """
    Create and return the Pyramid WSGI application.
    Registers routes, views, templates, and authentication tween.
    """

    settings = settings or {}
    settings.setdefault("jinja2.directories", "app/templates")

    settings["trusted_proxy_headers"] = [
        "x-forwarded-proto",
        "x-forwarded-host",
        "x-forwarded-port"
    ]

    config = Configurator(settings=settings)
    config.add_settings({"trusted_proxy_headers": ["x-forwarded-proto", "x-forwarded-host", "x-forwarded-port"]})

    config.add_request_method(
        lambda req: req.headers.get("X-Forwarded-Proto", req.scheme),
        "real_scheme",
        reify=True,
    )

    config.include("pyramid_jinja2")
    config.add_tween("app.pyramid_auth.auth_tween_factory")

    # Application routes
    config.add_route("home", "/app")
    config.add_route("home_slash", "/app/")
    config.add_route("list", "/app/submissions")
    config.add_route("edit", "/app/edit/{_id}")
    config.add_route("whoami", "/app/_debug/whoami")
    config.add_request_method(lambda r: getattr(r, "niu", None), "niu", reify=True)

    # Scan for @view_config declarations in the views module
    config.scan("app.views")

    return config.make_wsgi_app()
