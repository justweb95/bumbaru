import numpy as np, glob
from PIL import Image
from scipy import ndimage
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/'
f=[p for p in glob.glob(W+'dl/*.png') if Image.open(p).size==(1024,1024)][0]
R=np.array(Image.open(f).convert('RGBA')).astype(float)
rm=R[:,:,3]>100
print('radar occupancy', rm.mean())
Z=np.load(W+'work/hm_s.npy'); mm=np.isfinite(Z)  # rows=y up, res .1, x0=-81,y0=-75
# downsample model mask to 0.5mm grid
mmd=mm[::5,::5]; Hm,Wm=mmd.shape
# for candidate k (mm per radar px) and offset, map radar to model grid
best=(0,)
ys,xs=np.nonzero(rm)
for k in np.arange(0.13,0.22,0.0025):
    # radar px -> world mm: X=(x-ox)*k, Y=-(y-oy)*k  ; model grid idx: (X+81)/0.5, (Y+75)/0.5
    # rasterize radar at model grid for k with offsets via cross-correlation
    gx=(xs*k/0.5).astype(int); gy=(-ys*k/0.5).astype(int); gy-=gy.min(); 
    A=np.zeros((gy.max()+1,gx.max()+1)); A[gy,gx]=1
    A=A[::-1] if False else A
    # correlate: score(dx,dy)= sum A shifted * mmd
    from scipy.signal import fftconvolve
    P=np.pad(mmd.astype(float),((A.shape[0],A.shape[0]),(A.shape[1],A.shape[1])))
    C=fftconvolve(P,A[::-1,::-1],mode='valid')
    # IoU approx = C/(A.sum()+mmd.sum()-C)
    iou=C/(A.sum()+mmd.sum()-C)
    i=np.unravel_index(np.argmax(iou),iou.shape)
    if iou[i]>best[0]: best=(iou[i],k,i,A.shape,gx.min(),ys.max())
print(best)
iou,k,(iy,ix),ash,_,ysmax=best
# radar pixel (x,y) -> grid (gx,gy) = (x*k/.5, (ymax_y - y)*k/.5) relative; placed at P offset -> model grid index = g + (iy - A.shape[0]) etc
# P index (r,c) = model (r - ash0, c - ash1); A placed with origin at (iy,ix) in P
ymin=ys.min()
def r2w(x,y):
    gx=x*k/0.5; gy=(-y*k/0.5)-(-ys.max()*k/0.5)  # gy relative to min
    r=iy+gy-ash[0]; c=ix+gx-ash[1]
    return c*0.5-81, r*0.5-75
np.save(W+'work/r2w.npy',np.array([k,iy,ix,ash[0],ash[1],ys.max()]))
for nm,(x,y) in {'Bfountain':(497,214),'Tspawn':(100,690),'CT':(895,340),'Asite':(810,690),'Pit':(890,830)}.items():
    print(nm, r2w(x,y))
