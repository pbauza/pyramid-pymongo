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

    config = Configurator(settings=settings)
    config.include("pyramid_jinja2")
    config.add_tween("app.pyramid_auth.auth_tween_factory")

    # Application routes
    config.add_route("home", "/app")
    config.add_route("home_slash", "/app/")
    config.add_route("list", "/submissions")
    config.add_route("edit", "/edit/{_id}")
    config.add_route("whoami", "/_debug/whoami")

    # Scan for @view_config declarations in the views module
    config.scan("app.views")

    return config.make_wsgi_app()
