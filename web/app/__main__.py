"""
Module entry point to run the app with Waitress.
"""
import os
from waitress import serve
from . import main

if __name__ == "__main__":
    wsgi_app = main()
    host = "0.0.0.0"
    port = int(os.getenv("PORT", "8000"))
    serve(wsgi_app, host=host, port=port)
