import re
import os.path
import psycopg2
from psycopg2.extras import DictCursor
import cherrypy
from cherrypy.lib.static import serve_file



rus_letters = "абвгдеёжзийклмнопрстуфхцчшщьыъэюя"

def stem_crh (word):
    word = word.replace("ü", "u")
    word = word.replace("ı", "i")
    word = word.replace("ö", "o")
    word = word.replace("â", "a")
    word = word.replace("ş", "s")
    word = word.replace("ğ", "g")
    word = word.replace("ç", "c")
    word = word.replace("ñ", "n")
    word = word.replace("q", "k")
    return word

def stem_ru(word):
    word = word.replace("ё", "е")
    return word

def get_stem(word):
    word = word.lower()
    if rus_letters.find(word[0]) != -1:
        return stem_ru(word)
    else:
        return stem_crh(word)

def get_all_forms(word):
    l = len(word)
    out = []
    while l:
        out.append(word[0:l])
        l -= 1
    return out

current_dir = os.path.dirname(os.path.abspath(__file__))
conn = psycopg2.connect(database="qlugat",
                        user="qlugat",
                        password="qlugat",
                        host="localhost",
                        port=5432)

class Root(object):
    @cherrypy.expose
    def index(self):
        return serve_file(os.path.join(current_dir, 'static/index.html'))


    @cherrypy.expose
    @cherrypy.tools.json_out()
    def suggest(self, token):
        cur = conn.cursor()
        cur.execute("""SELECT * FROM "WORD" WHERE stem LIKE %s ORDER BY word""", [get_stem(token)+'%'])
        res = cur.fetchall()

        cur.close()
        return [x[2] for x in res] 

    @cherrypy.expose
    @cherrypy.tools.json_out()
    def get_json(self, word='', stem=''):
        print("DEBUG: get_json(word=", word, ")")
        out = {}
        if not word and not stem:
            return out
        
        cur = conn.cursor(cursor_factory=DictCursor)

        if word:
            cur.execute("""SELECT * FROM "WORD" WHERE word = %s""", [word])
        else:
            cur.execute("""SELECT * FROM "WORD" WHERE stem = %s""", [get_stem(stem)])            
        
        word = cur.fetchone()
        if (word):
            cur.execute("""SELECT * FROM "ARTICLE" WHERE word_id = %s""", [word['id']])
            articles = [{"text": x['text'], "accent_pos": x['accent_pos']} for x in cur.fetchall()]
            out = {
                "word": word['word'],
                "shortening_pos": word['shortening_pos'],
                "articles": articles
            }

        cur.close()
        return out

    @cherrypy.expose
    def admin(self):
        return "ADMIN"

if __name__ == "__main__":
    userpassdict = {
        "admin": ""
    }
    current_dir = os.path.dirname(os.path.abspath(__file__))
    cherrypy.config.update({
        "server.socket_port": 8080,
    })
    config = {
        "/": {
            'tools.response_headers.on': True,
            'tools.response_headers.headers': [
                ('Access-Control-Allow-Origin', '*'),
                ('Cache-Control', 'public,max-age=86400')
            ],
        },
        "/static": {
            "tools.staticdir.on": True,
            "tools.staticdir.dir": current_dir + "/static",
        },
        "/admin": {
            'tools.auth_basic.on': True,
            'tools.auth_basic.realm': 'admin',
            'tools.auth_basic.checkpassword': cherrypy.lib.auth_basic.checkpassword_dict(userpassdict)
        }
    }
    cherrypy.quickstart(Root(), "/", config)
