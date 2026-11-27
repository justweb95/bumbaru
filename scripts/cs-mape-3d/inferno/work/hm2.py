import numpy as np,sys
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
sys.path.insert(0,W)
from raster import heightmap
d=np.load(W+'map_s.npz'); V=d['v'].astype(float);F=d['f'].astype(np.int64)
res=0.1; x0,y0=-81,-75; Wd,Hd=int(162/res),int(150/res)
Z=heightmap(V,F,res,x0,y0,Wd,Hd)
np.save(W+'hm_s.npy',Z)
from PIL import Image
m=np.isfinite(Z); im=np.zeros(Z.shape); im[m]=Z[m]/Z[m].max()*235+20
Image.fromarray(im[::-1].astype(np.uint8)).resize((810,750)).save(W+'hm_s.png')
