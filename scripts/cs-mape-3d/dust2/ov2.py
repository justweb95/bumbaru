import numpy as np
from PIL import Image
from scipy import ndimage as nd
H=np.load('H.npy'); s,oy,ox=np.load('align.npy'); res=0.25
Hi=H[::-1]
# hillshade
Z=np.nan_to_num(Hi,nan=np.nanmin(H)-5)
gy,gx=np.gradient(Z,res)
L=np.array([-1,1,1.5]);L/=np.linalg.norm(L)
n=np.dstack([-gx,gy,np.ones_like(Z)]); n/=np.linalg.norm(n,axis=2,keepdims=True)
sh=np.clip((n*L).sum(2),0,1)
hn=(Z-np.nanmin(H))/(np.nanmax(H)-np.nanmin(H))
g=(0.35+0.65*sh)*(0.4+0.6*hn)
img=np.dstack([g*230,g*203,g*160]).astype(np.uint8)
img[~np.isfinite(Hi)]=[255,255,255]
k=s/res
r=np.array(Image.open('ref/r1.png').resize((int(1024*k),int(1024*k))))
R=r[...,3]>20
E=R^nd.binary_erosion(R,iterations=1)
# also radar object outlines (light gray boxes) and orange/green zones
col=r[...,:3].astype(int)
orange=(col[...,0]>200)&(col[...,1]<150)&(col[...,2]<80)&R
green=(col[...,1]>180)&(col[...,0]<100)&R
box=(col.min(2)>130)&(np.ptp(col,axis=2)<20)&R
Eb=box^nd.binary_erosion(box)
def put(mask,c):
    ys,xs=np.nonzero(mask); ys=ys+int(oy); xs=xs+int(ox)
    ok=(ys>=0)&(ys<img.shape[0])&(xs>=0)&(xs<img.shape[1]); img[ys[ok],xs[ok]]=c
put(E,[255,0,0]); put(Eb,[0,0,255]); put(orange,[255,140,0]); put(green,[0,200,0])
im=Image.fromarray(img).resize((img.shape[1]*2,img.shape[0]*2),Image.NEAREST)
im.save('ov2.png')
W,Hh=im.size
im.crop((0,0,W//2+100,Hh//2+100)).save('q_tl.png'); im.crop((W//2-100,0,W,Hh//2+100)).save('q_tr.png')
im.crop((0,Hh//2-100,W//2+100,Hh)).save('q_bl.png'); im.crop((W//2-100,Hh//2-100,W,Hh)).save('q_br.png')
