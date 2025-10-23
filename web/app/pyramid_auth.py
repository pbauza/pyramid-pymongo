# web/app/pyramid_auth.py
import os
import jwt
import logging
from pyramid.interfaces import IRequest
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
        user_id = None
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
                user_id = claims.get("sub")
                if user_id:
                    logger.info("Authenticated NIU: %s", user_id)
            except Exception as e:
                logger.warning("JWT validation failed: %s", e)
                user_id = None

        # Expose to views
        request.authenticated_userid = user_id
        if user_id:
            request.effective_principals = [Authenticated, f"user:{user_id}"]
        else:
            request.effective_principals = []

        return handler(request)

    return auth_tween
