# web/app/pyramid_auth.py
import os
import jwt
import logging
from pyramid.security import Authenticated
from pyramid.request import Request

logger = logging.getLogger(__name__)

PUBLIC_KEY_PATH = os.environ.get("JWT_PUBLIC_KEY", "/etc/keys/jwt-public.pem")
COOKIE_NAME = os.environ.get("JWT_COOKIE_NAME", "session_jwt")
ALGORITHMS = ["RS256"]

with open(PUBLIC_KEY_PATH, "rb") as f:
    PUBLIC_KEY = f.read()

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
                    audience="pyramid-app",  # must match PHP 'aud'
                    options={"require": ["sub", "exp", "iat"]}
                )
                niu = claims.get("sub")
                if niu:
                    logger.info("Authenticated NIU: %s", niu)
            except Exception as e:
                logger.warning("JWT validation failed: %s", e)
                niu = None

        # Expose NIU to views without touching read-only props
        request.niu = niu                       # custom attribute for app
        if niu:
            request.environ["REMOTE_USER"] = niu  # standard place many apps look at

        # expose principals via a custom attr as well
        request.user_principals = [Authenticated, f"user:{niu}"] if niu else []

        return handler(request)

    return auth_tween
