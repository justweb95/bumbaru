import numpy as np,sys
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
sys.path.insert(0,W)
from render import render
from scipy import ndimage as nd
Z=np.load(W+'hm_s.npy'); ID=np.ones(Z.shape,int)
top=np.isfinite(Z)&(Z>Z[np.isfinite(Z)].max()-0.3)
lab,n=nd.label(top); s=nd.sum(top,lab,range(1,n+1))
o=np.argsort(s)[::-1]
for j in o[:15]:
    rr,cc=np.nonzero(lab==j+1); print(j+1,int(s[j]*0.01),'mm2 x',round(cc.min()*0.1-81,1),round(cc.max()*.1-81,1),'y',round(rr.min()*.1-75,1),round(rr.max()*.1-75,1))
render(Z,ID,{1:'#E6CBA0'},0.1,W+'r0.png')
from PIL import Image
Image.open(W+'r0.png').resize((800,738)).save(W+'r0s.png')
