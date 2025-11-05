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

PUBLIC_KEY_PATH = os.environ.get("JWT_PUBLIC_KEY", "/etc/keys/jwt-public.pem")
COOKIE_NAME = os.environ.get("JWT_COOKIE_NAME", "session_jwt")
ALGORITHMS = ["RS256"]
LEEWAY = int(os.environ.get("JWT_LEEWAY", "300"))  # segundos de tolerancia

# Rutas públicas (no fuerzan login)
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

        # Si la clave no cargó al inicio, reintenta perezosamente
        if PUBLIC_KEY is None:
            PUBLIC_KEY = _load_public_key()

        niu = None
        token = request.cookies.get(COOKIE_NAME)
        path = request.path_info or "/"
        # Si usas auth_request + proxy, este header ayuda a detectar el path real
        original_uri = request.environ.get("HTTP_X_ORIGINAL_URI", path)

        if token:
            # Quita comillas si algún proxy las añadió
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
                        "require": ["sub", "exp"],  # iat opcional; exp sí requerido
                    },
                    leeway=LEEWAY,
                )

                niu = claims.get("sub")
                if niu:
                    logger.info("JWT ok · NIU=%s · exp=%s", niu, claims.get("exp"))
                else:
                    logger.warning("JWT sin 'sub': %s", claims)

            except ExpiredSignatureError as e:
                logger.warning("JWT expirado: %s", e)
            except ImmatureSignatureError as e:
                logger.warning("JWT aún no válido (nbf/iat): %s", e)
            except InvalidSignatureError as e:
                logger.error("JWT firma inválida: %s", e)
            except DecodeError as e:
                logger.error("JWT mal formado/decodificación: %s", e)
            except Exception as e:
                logger.error("Fallo validando JWT: %s", e)

        # Exponer NIU a las vistas
        request.niu = niu
        if niu:
            request.environ["REMOTE_USER"] = niu

        # Proteger /app (usar original_uri por si Nginx reescribe paths)
        needs_auth = original_uri.startswith("/app") and not _is_public(original_uri)

        if needs_auth and not niu:
            # Redirige a / para relanzar CAS + emisión del JWT por PHP
            return HTTPFound(location="/")

            # Si prefieres ver el 401 para depurar, usa esto:
            # return HTTPUnauthorized("Authentication required")

        return handler(request)

    return auth_tween
