import numpy as np
from PIL import Image
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/'
k,iy,ix,a0,a1,ysmax=np.load(W+'work/r2w.npy')
R=np.array(Image.open(W+'dl/radar1024.png').convert('RGBA')).astype(float)
Z=np.load(W+'work/hm_s.npy'); H,Wd=Z.shape
# model pixel (r,c) at res .1 -> world -> radar px
r,c=np.mgrid[0:H,0:Wd]
X=c*0.1-81+0.05; Y=r*0.1-75+0.05
cc=(X+81)/0.5; rr=(Y+75)/0.5
gx=cc-ix+a1; gy=rr-iy+a0
x=gx*0.5/k; y=-(gy*0.5/k - ysmax*k/0.5*0.5/k)
y=ysmax - gy*0.5/k
xi=np.clip(x.astype(int),0,1023); yi=np.clip(y.astype(int),0,1023)
S=R[yi,xi]
m=np.isfinite(Z); g=np.zeros(Z.shape); g[m]=Z[m]/Z[m].max()*200+40
out=np.stack([g,g,g],-1)
a=(S[:,:,3:4]/255)*0.55
out=out*(1-a)+S[:,:,:3]*a
np.save(W+'work/radar_on_model.npy',S.astype(np.uint8))
Image.fromarray(out[::-1].astype(np.uint8)).resize((1080,1000)).save(W+'work/overlay.png')
