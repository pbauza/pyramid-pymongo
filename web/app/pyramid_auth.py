# web/app/pyramid_auth.py
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

# Path to the public key used to verify JWT signatures (RS256)
PUBLIC_KEY_PATH = os.environ.get("JWT_PUBLIC_KEY", "/etc/keys/jwt-public.pem")

# Name of the cookie that stores the JWT
COOKIE_NAME = os.environ.get("JWT_COOKIE_NAME", "session_jwt")

# Supported signing algorithms (RS256 expected)
ALGORITHMS = ["RS256"]

# Allowable clock skew between JWT 'iat'/'exp' and current time (in seconds)
LEEWAY = int(os.environ.get("JWT_LEEWAY", "300"))

# URL path prefixes that are considered public and do not require authentication
PUBLIC_PATH_PREFIXES = ("/static/", "/_health")


def _is_public(path: str) -> bool:
    """Return True if the requested path is public and does not require authentication."""
    return path == "/" or any(path.startswith(p) for p in PUBLIC_PATH_PREFIXES)


def _load_public_key():
    """Read and return the RSA public key used for verifying JWT tokens."""
    try:
        with open(PUBLIC_KEY_PATH, "rb") as f:
            key = f.read()
        if not key:
            raise ValueError("public key empty")
        return key
    except Exception as e:
        logger.error("Cannot read public key at %s: %s", PUBLIC_KEY_PATH, e)
        return None


# Cache the public key on startup (lazy reloading if missing)
PUBLIC_KEY = _load_public_key()


def auth_tween_factory(handler, registry):
    """
    Pyramid authentication tween.
    This middleware intercepts all requests and validates the JWT stored in the session cookie.
    If the token is valid, the user identity (NIU) is attached to the request.
    If the token is invalid or missing, the user is redirected to '/' for CAS authentication.
    """
    def auth_tween(request: Request):
        global PUBLIC_KEY

        # Reload the key if it wasn't available during startup
        if PUBLIC_KEY is None:
            PUBLIC_KEY = _load_public_key()

        niu = None
        token = request.cookies.get(COOKIE_NAME)
        path = request.path_info or "/"

        # Retrieve the original URI before proxy rewriting (for Nginx auth_request setups)
        original_uri = request.environ.get("HTTP_X_ORIGINAL_URI", path)

        if token:
            # Strip potential quotes accidentally added by proxies
            if token.startswith('"') and token.endswith('"'):
                token = token[1:-1]

            try:
                if PUBLIC_KEY is None:
                    raise RuntimeError("public key not loaded")

                # Decode and verify the JWT using RS256 and the configured public key
                claims = jwt.decode(
                    token,
                    PUBLIC_KEY,
                    algorithms=ALGORITHMS,
                    options={
                        "verify_aud": False,
                        "require": ["sub", "exp"],  # Require subject and expiration claims
                    },
                    leeway=LEEWAY,
                )

                # Extract user identifier (NIU) from the 'sub' claim
                niu = claims.get("sub")
                if niu:
                    logger.info("JWT ok · NIU=%s · exp=%s", niu, claims.get("exp"))
                else:
                    logger.warning("JWT decoded but missing 'sub' claim: %s", claims)

            # Specific JWT validation errors (logged with detail for debugging)
            except ExpiredSignatureError as e:
                logger.warning("JWT expired: %s", e)
            except ImmatureSignatureError as e:
                logger.warning("JWT not yet valid (nbf/iat): %s", e)
            except InvalidSignatureError as e:
                logger.error("Invalid JWT signature: %s", e)
            except DecodeError as e:
                logger.error("Malformed or undecodable JWT: %s", e)
            except Exception as e:
                logger.error("Unexpected JWT validation failure: %s", e)

        # Attach user identity to the request (if present)
        request.niu = niu
        if niu:
            request.environ["REMOTE_USER"] = niu

        # Require authentication for all paths under /app (except explicitly public ones)
        needs_auth = original_uri.startswith("/app") and not _is_public(original_uri)

        if needs_auth and not niu:
            # Redirect unauthenticated users to the CAS entry point
            return HTTPFound(location="/")

            # For debugging, the following can be used instead:
            # return HTTPUnauthorized("Authentication required")

        # Continue normal request processing
        return handler(request)

    return auth_tween
