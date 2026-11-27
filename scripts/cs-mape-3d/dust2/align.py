import numpy as np
from PIL import Image
from scipy import ndimage as nd
H=np.load('H.npy'); x0,y0,res=np.load('grid.npy')
M=np.isfinite(H)[::-1]  # image coords, row0 = max y
r=np.array(Image.open('ref/r1.png'))
R=r[...,3]>20
ys,xs=np.nonzero(R); print('ref bbox',xs.min(),xs.max(),ys.min(),ys.max())
ys2,xs2=np.nonzero(M); print('model bbox px',xs2.min(),xs2.max(),ys2.min(),ys2.max(), M.shape)
# search: ref pixel -> model mm : mx = a*(px) + bx ; my(img rows) same scale
best=None
Mi=M.astype(np.uint8)
for s in np.linspace(0.20,0.26,25):  # mm per ref px
    # resample ref into model grid
    for dx in np.arange(-30,31,2):
        for dy in np.arange(-30,31,2):
            pass
# faster: resize ref by scale then cross-correlate
from scipy.signal import fftconvolve
for s in np.linspace(0.20,0.26,31):
    k=s/res  # model px per ref px
    Rs=np.array(Image.fromarray(R.astype(np.uint8)*255).resize((int(1024*k),int(1024*k)),Image.BILINEAR))>127
    A=Mi*2-1.0; Bk=Rs*2-1.0
    c=fftconvolve(A,Bk[::-1,::-1],mode='full')
    j,i=np.unravel_index(np.argmax(c),c.shape)
    if best is None or c[j,i]>best[0]: best=(c[j,i],s,j-Rs.shape[0]+1,i-Rs.shape[1]+1,Rs)
print(best[:4])
_,s,oy,ox,Rs=best
# overlay image
O=np.zeros(M.shape+(3,),np.uint8)
O[M]=[120,120,120]
P=np.zeros_like(M)
h,w=Rs.shape
ys_,xs_=np.nonzero(Rs); ys_+=oy; xs_+=ox
ok=(ys_>=0)&(ys_<M.shape[0])&(xs_>=0)&(xs_<M.shape[1])
P[ys_[ok],xs_[ok]]=True
O[P&~M]=[255,0,0]; O[M&~P]=[0,120,255]; O[P&M]=[200,200,200]
Image.fromarray(O).save('overlay_mask.png')
np.save('align.npy',np.array([s,oy,ox]))
# also blended color overlay
k=s/res
rc=Image.open('ref/r1.png').convert('RGBA').resize((int(1024*k),int(1024*k)))
base=Image.fromarray(((np.nan_to_num((H-np.nanmin(H))/(np.nanmax(H)-np.nanmin(H)))[::-1])*255).astype(np.uint8)).convert('RGBA')
canvas=Image.new('RGBA',base.size,(0,0,0,0)); canvas.paste(rc,(ox,oy),rc)
Image.blend(base,canvas,0.5).save('overlay_ref.png')
