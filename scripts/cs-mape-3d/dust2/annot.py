import numpy as np
from PIL import Image, ImageDraw
H=np.load('Hf.npy'); res=0.1; S=float(np.load('scale.npy'))
s,oy,ox=np.load('align.npy'); k=s/0.25
def r2f(u,v):
    col=u*k+ox; row=v*k+oy
    return ((col+0.5)*0.25*S, (241.828338-(row+0.5)*0.25)*S)
Hi=H[::-1]
Z=np.nan_to_num(Hi,nan=0)
gy,gx=np.gradient(Z,res)
L=np.array([-1,1,1.5]);L/=np.linalg.norm(L)
n=np.dstack([-gx,gy,np.ones_like(Z)]); n/=np.linalg.norm(n,axis=2,keepdims=True)
sh=np.clip((n*L).sum(2),0,1); hn=Z/np.nanmax(H)
g=(0.35+0.65*sh)*(0.4+0.6*hn)
img=np.dstack([g*230,g*203,g*160]).astype(np.uint8); img[~np.isfinite(Hi)]=255
im=Image.fromarray(img).convert('RGB'); d=ImageDraw.Draw(im)
Hh=H.shape[0]
P=lambda x,y:(x/res,Hh-y/res)
for x in range(0,150,10):
    d.line([P(x,0),P(x,160)],fill=(0,160,255) if x%50 else (0,0,255),width=1); d.text(P(x+0.5,1),str(x),fill=(0,0,255))
for y in range(0,170,10):
    d.line([P(0,y),P(150,y)],fill=(0,160,255) if y%50 else (0,0,255),width=1); d.text(P(0.5,y+0.5),str(y),fill=(0,0,255))
zones={'B':(170,80,255,175),'A':(780,135,850,210),'CT':(570,150,680,270),'T':(295,875,490,960)}
for nme,(u0,v0,u1,v1) in zones.items():
    a=r2f(u0,v0); b=r2f(u1,v1)
    d.rectangle([P(a[0],a[1]),P(b[0],b[1])],outline=(255,0,0),width=3); d.text(P(a[0],a[1]),nme,fill=(255,0,0))
    print(nme,'x',round(a[0],1),round(b[0],1),'y',round(b[1],1),round(a[1],1))
im.save('annot.png')
