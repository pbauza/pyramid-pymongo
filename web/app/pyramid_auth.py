import os
import logging
import jwt
from jwt import (
    ExpiredSignatureError,
    ImmatureSignatureError,
    InvalidSignatureError,
    DecodeError,
)
from pyramid.request import Request
from pyramid.httpexceptions import HTTPFound, HTTPUnauthorized

logger = logging.getLogger(__name__)
logger.setLevel(logging.INFO)

PUBLIC_KEY_PATH = os.environ.get("JWT_PUBLIC_KEY", "/etc/keys/jwt-public.pem")
COOKIE_NAME = os.environ.get("JWT_COOKIE_NAME", "session_jwt")
ALGORITHMS = ["RS256"]
LEEWAY = int(os.environ.get("JWT_LEEWAY", "300"))  # tolerance in seconds

# Public routes (no auth required)
PUBLIC_PATH_PREFIXES = ("/static/", "/_health")


def _is_public(path: str) -> bool:
    return path == "/" or any(path.startswith(p) for p in PUBLIC_PATH_PREFIXES)


def _load_public_key():
    try:
        with open(PUBLIC_KEY_PATH, "rb") as f:
            key = f.read()
        if not key:
            raise ValueError("public key empty")
        return key
    except Exception as e:
        logger.error("Cannot read public key at %s: %s", PUBLIC_KEY_PATH, e)
        return None


PUBLIC_KEY = _load_public_key()


def auth_tween_factory(handler, registry):
    def auth_tween(request: Request):
        global PUBLIC_KEY

        if PUBLIC_KEY is None:
            PUBLIC_KEY = _load_public_key()

        niu = None
        full_name = None

        token = request.cookies.get(COOKIE_NAME)
        path = request.path_info or "/"
        original_uri = request.environ.get("HTTP_X_ORIGINAL_URI", path)

        if token:
            if token.startswith('"') and token.endswith('"'):
                token = token[1:-1]

            try:
                if PUBLIC_KEY is None:
                    raise RuntimeError("public key not loaded")

                claims = jwt.decode(
                    token,
                    PUBLIC_KEY,
                    algorithms=ALGORITHMS,
                    options={
                        "verify_aud": False,
                        "require": ["sub", "exp"],
                    },
                    leeway=LEEWAY,
                )

                niu = claims.get("sub")
                # Try extracting name or full_name
                full_name = (
                    claims.get("name")
                    or claims.get("full_name")
                    or claims.get("given_name")
                    or None
                )

                if niu:
                    logger.info(
                        "JWT ok · NIU=%s · Name=%s · exp=%s",
                        niu,
                        full_name or "(no name)",
                        claims.get("exp"),
                    )
                else:
                    logger.warning("JWT without 'sub': %s", claims)

            except ExpiredSignatureError as e:
                logger.warning("JWT expired: %s", e)
            except ImmatureSignatureError as e:
                logger.warning("JWT not yet valid: %s", e)
            except InvalidSignatureError as e:
                logger.error("JWT invalid signature: %s", e)
            except DecodeError as e:
                logger.error("JWT decode error: %s", e)
            except Exception as e:
                logger.error("JWT validation failed: %s", e)

        # Expose data to Pyramid
        request.niu = niu
        request.environ["REMOTE_USER"] = niu
        if full_name:
            request.environ["REMOTE_NAME"] = full_name

        # Protect /app
        needs_auth = original_uri.startswith("/app") and not _is_public(original_uri)
        if needs_auth and not niu:
            return HTTPFound(location="/")  # Trigger CAS login

        return handler(request)

    return auth_tween
