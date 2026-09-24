const express = require('express');
const session = require('express-session');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'T3K-Prime';
const SESSION_SECRET = process.env.SESSION_SECRET || 'T3K-change-this-secret';
const DATA_FILE = path.join(__dirname, 'applications.json');

app.use(express.json({limit:'50kb'}));
app.use(session({secret:SESSION_SECRET,resave:false,saveUninitialized:false,cookie:{httpOnly:true,sameSite:'lax',secure:false,maxAge:1000*60*60*8}}));

function readApps(){try{return JSON.parse(fs.readFileSync(DATA_FILE,'utf8'));}catch{return []}}
function writeApps(apps){fs.writeFileSync(DATA_FILE,JSON.stringify(apps,null,2));}
function ownerOnly(req,res,next){if(req.session.owner)return next();return res.status(401).json({error:'Nicht autorisiert'});}

app.post('/api/applications',(req,res)=>{
  const {name,age,reason,skills}=req.body||{};
  if(!name||!age||!reason||!skills) return res.status(400).json({error:'Bitte alle Pflichtfelder ausfüllen.'});
  const safeSkills={blaze:Number(skills.blaze),l4l:Number(skills.l4l),speed:Number(skills.speed),stamina:Number(skills.stamina)};
  if(Object.values(safeSkills).some(v=>!Number.isInteger(v)||v<1||v>5)) return res.status(400).json({error:'Ungültige Selbsteinschätzung.'});
  const apps=readApps();
  const entry={id:Date.now().toString(36)+Math.random().toString(36).slice(2,7),createdAt:new Date().toISOString(),name:String(name).slice(0,120),age:String(age).slice(0,3),reason:String(reason).slice(0,3000),skills:safeSkills};
  apps.unshift(entry); writeApps(apps); res.json({ok:true});
});

app.post('/api/login',(req,res)=>{if(req.body?.password===ADMIN_PASSWORD){req.session.owner=true;return res.json({ok:true});}res.status(401).json({error:'Falsches Passwort'});});
app.post('/api/logout',(req,res)=>req.session.destroy(()=>res.json({ok:true})));
app.get('/api/me',(req,res)=>res.json({owner:!!req.session.owner}));
app.get('/api/applications',ownerOnly,(req,res)=>res.json(readApps()));
app.delete('/api/applications/:id',ownerOnly,(req,res)=>{const apps=readApps();const next=apps.filter(a=>a.id!==req.params.id);writeApps(next);res.json({ok:true});});

app.use(express.static(path.join(__dirname,'public')));
app.get('/admin',(req,res)=>res.sendFile(path.join(__dirname,'public','admin.html')));
app.listen(PORT,()=>console.log(`T3K läuft auf http://localhost:${PORT}`));
