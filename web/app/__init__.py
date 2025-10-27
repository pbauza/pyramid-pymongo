"""
Pyramid application factory and setup.

"""
import os
from pyramid.config import Configurator


def main(global_config=None, **settings):
    """
    Create and return a Pyramid WSGI application.
    We keep configuration minimal and driven by environment variables.
    """
    # Merge env-based settings so templates can use them if needed
    settings = settings or {}
    settings.setdefault("jinja2.directories", "app/templates")

    config = Configurator(settings=settings)
    config.include("pyramid_jinja2")  # Enable Jinja2 templating
    config.add_tween("app.pyramid_auth.auth_tween_factory")

    # Routes
    config.add_route("home", "/")
    config.add_route("list", "/submissions")
    config.add_route("edit", "/edit/{_id}")

    # Scan views module for @view_config
    config.scan("app.views")

    return config.make_wsgi_app()
