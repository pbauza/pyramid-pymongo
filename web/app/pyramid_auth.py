# web/app/pyramid_auth.py
import os
import logging
import jwt
from pyramid.request import Request
from pyramid.httpexceptions import HTTPFound, HTTPUnauthorized

logger = logging.getLogger(__name__)

PUBLIC_KEY_PATH = os.environ.get("JWT_PUBLIC_KEY", "/etc/keys/jwt-public.pem")
COOKIE_NAME = os.environ.get("JWT_COOKIE_NAME", "session_jwt")
ALGORITHMS = ["RS256"]

with open(PUBLIC_KEY_PATH, "rb") as f:
    PUBLIC_KEY = f.read()

# Public paths that should NOT enforce login
PUBLIC_PATH_PREFIXES = ("/static/", "/_health")
def _is_public(path: str) -> bool:
    return path == "/" or any(path.startswith(p) for p in PUBLIC_PATH_PREFIXES)

def auth_tween_factory(handler, registry):
    def auth_tween(request: Request):
        niu = None
        token = request.cookies.get(COOKIE_NAME)
        path = request.path_info or "/"
        original_uri = request.environ.get("HTTP_X_ORIGINAL_URI", path)

        if token:
            try:
                claims = jwt.decode(
                    token,
                    PUBLIC_KEY,
                    algorithms=["RS256"],
                    options={"verify_aud": False, "require": ["sub", "exp", "iat"]}
                )

                niu = claims.get("sub")
                if niu:
                    logger.info("Authenticated NIU: %s", niu)
                else:
                    logger.warning("JWT decoded but no 'sub' claim: %s", claims)
            except Exception as e:
                # Log WHY it failed (invalid audience, bad signature, expired, etc.)
                logger.error("JWT validation failed: %s", e)

        # Expose NIU for views
        request.niu = niu
        if niu:
            request.environ["REMOTE_USER"] = niu

        # Enforce auth for everything under /app (use original URI to avoid nginx prefix stripping issues)
        if original_uri.startswith("/app") and not _is_public(original_uri) and not niu:
            return HTTPUnauthorized("Authentication required")

        return handler(request)

    return auth_tween
