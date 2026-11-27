import numpy as np,sys,os
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
sys.path.insert(0,W)
from raster import heightmap
from render import render
from PIL import Image
res=0.1; x0,y0=-80.05,-73.85; Wd,Hd=1601,1478
names=['base','roof','T','CT','A','B','K','F']
col={0:'#E6CBA0',1:'#C8102E',2:'#C8102E',3:'#1B2A5C',4:'#C8102E',5:'#C8102E',6:'#6B4226',7:'#1B2A5C'}
Zall=np.full((Hd,Wd),-np.inf); ID=np.zeros((Hd,Wd),int)
for i,n in enumerate(names):
    if not os.path.exists(W+f'part_{n}.npz'): continue
    d=np.load(W+f'part_{n}.npz'); Zp=heightmap(d['v'].astype(float),d['f'].astype(np.int64),res,x0,y0,Wd,Hd)
    better=Zp>Zall+(0.005 if i else 0); Zall[better]=Zp[better]; ID[better]=i
np.save(W+'final_hm.npy',Zall); np.save(W+'final_id.npy',ID)
out=sys.argv[1] if len(sys.argv)>1 else W+'prev.png'
render(Zall,ID,col,res,out)
im=Image.open(out); print(im.size)
im.resize((im.size[0]//2,im.size[1]//2)).save(W+'prev_s.png')
