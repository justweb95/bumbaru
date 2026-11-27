import re, numpy as np, trimesh
SRC='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/ravne/src/'
OBJ={'ancient':(1,3.72493951),'cache':(2,0.279107154),'mirage':(3,10.9440078),'dust2':(4,9.21167256),'inferno':(5,1.43684433),'nuke':(6,2.46725359),'vertigo':(7,1.50272784)}
def load_raw(name):
    i,s=OBJ[name]
    t=open(SRC+f'3D/Objects/object_{i}.model').read()
    v=np.array(re.findall(r'<vertex x="([^"]+)" y="([^"]+)" z="([^"]+)"',t),float)*s
    tri=re.findall(r'<triangle v1="(\d+)" v2="(\d+)" v3="(\d+)"([^/]*)/>',t)
    f=np.array([x[:3] for x in tri],int)
    paint=np.array([ (re.search(r'paint_color="([^"]+)"',x[3]).group(1) if 'paint_color' in x[3] else '') for x in tri])
    v=v-v.min(0)
    return v,f,paint
def load(name):
    v,f,p=load_raw(name)
    return trimesh.Trimesh(v,f,process=True)
