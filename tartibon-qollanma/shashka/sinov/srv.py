import http.server, json, os, re, subprocess, random, urllib.parse
B=os.path.dirname(os.path.abspath(__file__))
IDX='/tmp/claude-0/-home-user-Test/2ce4ef18-bede-5a60-9210-939b16ef3ee9/scratchpad/index_sh.php'
STUB = """<script>
window.__ERR=[];window.onerror=function(m){window.__ERR.push(String(m))};
window.addEventListener('unhandledrejection',function(e){window.__ERR.push('p: '+(e.reason&&e.reason.message||e.reason))});
(function(){function mk(){return new Proxy(function(){return mk()},{get:function(t,k){
 if(k==='initData')return 'stub';
 if(k==='initDataUnsafe')return window.__IDU||{user:{id:7}};
 if(k==='version')return '7.0'; if(k==='platform')return 'ios';
 if(k==='colorScheme')return 'light'; if(k==='themeParams')return {};
 if(k==='then')return undefined; if(k===Symbol.toPrimitive)return function(){return ''};
 return mk()},apply:function(){return mk()}})}
 window.Telegram={WebApp:mk()};})();
window.__API=[];window.__SEED=0;
var _f=window.fetch;
window.fetch=function(u,o){var a=String(u).split('api=')[1]||'';window.__API.push(a);
 if(a.indexOf('sh_')===0){return _f(u,o).then(function(r){return r.json()}).then(function(j){
   return {json:function(){return Promise.resolve(j)}}})}
 var j={ok:true,admin:false,chats:[],list:[],rows:[],items:[],problems:[],mn:{},st:{},
  sup:'TartibOnBot',guide:'',total:0,n:0,cnt:{},prices:{},orders:[],balance:0,
  today:'2026-11-10',regions:[],now:Date.now()/1000,
  phone:'+998901234567',tut:0,badges:{my_Kanal:0,my_Guruh:0,auc:0}};
 return Promise.resolve({json:function(){return Promise.resolve(j)}})};
</script>"""
html=None
def page():
    global html
    if html is None:
        s=open(IDX,encoding='utf-8').read()
        h=s[s.rfind('?><!doctype')+2:]
        h=re.sub(r'<\?php.*?\?>','"TartibOnBot"',h,flags=re.S)
        h=h.replace('<head>','<head>'+STUB,1)
        h=h.replace('<script src="https://telegram.org/js/telegram-web-app.js"></script>','')
        html=h
    return html
JS=open(os.path.join(B,'served.js'),encoding='utf-8').read()
class H(http.server.BaseHTTPRequestHandler):
    def log_message(self,*a): pass
    def send(self,body,ctype,code=200):
        b=body.encode() if isinstance(body,str) else body
        self.send_response(code);self.send_header('Content-Type',ctype)
        self.send_header('Content-Length',str(len(b)));self.end_headers();self.wfile.write(b)
    def do_GET(self):
        qs=urllib.parse.urlparse(self.path).query
        if 'shjs=' in qs: return self.send(JS,'text/javascript; charset=utf-8')
        return self.send(page(),'text/html; charset=utf-8')
    def do_POST(self):
        qs=urllib.parse.parse_qs(urllib.parse.urlparse(self.path).query)
        a=(qs.get('api') or [''])[0]
        n=int(self.headers.get('Content-Length') or 0)
        body=json.loads(self.rfile.read(n) or b'{}')
        if a=='sh_get':
            r={'ok':True,'oy':'2026-11','hozir':'2026-11','oylar':['2026-11','2026-10'],
               'oxiri':'30.11.2026','ver':'uz-manual-v4',
               'me':{'m':1240,'y':7,'plays':3,'rank':2},
               'top':[{'uid':9,'m':2100,'y':14,'name':'Aziz','username':'aziz'},
                      {'uid':7,'m':1240,'y':7,'name':'Siz','username':''}],
               'total':2,'chats':[{'id':1,'username':'@test_kanal','ctype':'Kanal'}]}
        elif a=='sh_start':
            seed=random.randint(1,4294967295)
            with open(os.path.join(B,'last_seed.txt'),'w') as f: f.write(str(seed))
            r={'ok':True,'run':'0'*32,'seed':seed,'ver':'uz-manual-v4'}
        elif a=='sh_fin':
            seed=int(open(os.path.join(B,'last_seed.txt')).read())
            p=subprocess.run(['php',os.path.join(B,'fin.php')],
                input=json.dumps({'seed':seed,'inputs':body.get('inputs',[]),'ticks':body.get('ticks',0)}),
                capture_output=True,text=True,timeout=60)
            r=json.loads(p.stdout or '{"ok":false,"error":"php xato"}')
        else:
            r={'ok':True}
        return self.send(json.dumps(r),'application/json')
http.server.HTTPServer(('127.0.0.1',8733),H).serve_forever()
