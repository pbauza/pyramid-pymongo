# pyramid_auth.py
import jwt
from pyramid.httpexceptions import HTTPUnauthorized

PUBLIC_KEY = open("/etc/keys/jwt-public.pem", "rb").read()

def auth_tween_factory(handler, registry):
    def auth_tween(request):
        token = request.cookies.get("session_jwt")
        if not token:
            raise HTTPUnauthorized()
        try:
            claims = jwt.decode(token, PUBLIC_KEY, algorithms=["RS256"], audience="pyramid", issuer="php-auth")
        except jwt.PyJWTError:
            raise HTTPUnauthorized()
        request.user = {"niu": claims["sub"]}
        return handler(request)
    return auth_tween
