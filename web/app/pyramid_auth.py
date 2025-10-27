# web/app/pyramid_auth.py
import os
import jwt
import logging
from pyramid.request import Request
from pyramid.httpexceptions import HTTPFound, HTTPUnauthorized

logger = logging.getLogger(__name__)

PUBLIC_KEY_PATH = os.environ.get("JWT_PUBLIC_KEY", "/etc/keys/jwt-public.pem")
COOKIE_NAME = os.environ.get("JWT_COOKIE_NAME", "session_jwt")
ALGORITHMS = ["RS256"]

with open(PUBLIC_KEY_PATH, "rb") as f:
    PUBLIC_KEY = f.read()

PUBLIC_PATH_PREFIXES = (
    "/static/",
)

def _is_public(path: str) -> bool:
    return any(path.startswith(p) for p in PUBLIC_PATH_PREFIXES) or path == "/"

def auth_tween_factory(handler, registry):
    def auth_tween(request: Request):
        niu = None
        token = request.cookies.get(COOKIE_NAME)

        if token:
            try:
                claims = jwt.decode(
                    token,
                    PUBLIC_KEY,
                    algorithms=ALGORITHMS,
                    audience="pyramid-app",
                    options={"require": ["sub", "exp", "iat"]}
                )
                niu = claims.get("sub")
                if niu:
                    logger.info("Authenticated NIU: %s", niu)
            except Exception as e:
                logger.warning("JWT validation failed: %s", e)

        # Expose NIU for views
        request.niu = niu
        if niu:
            request.environ["REMOTE_USER"] = niu

        # Enforce auth for /app/* (except public paths)
        path = request.path_info or "/"
        if path.startswith("/app") and not _is_public(path) and not niu:
            return HTTPUnauthorized(json_body={"error": "unauthorized"})

        return handler(request)

    return auth_tween
