import numpy as np, sys
from PIL import Image, ImageDraw
H=np.load('Hf.npy'); res=0.1; Hh=H.shape[0]
x0,x1,y0,y1,fn=float(sys.argv[1]),float(sys.argv[2]),float(sys.argv[3]),float(sys.argv[4]),sys.argv[5]
sub=H[int(y0/res):int(y1/res), int(x0/res):int(x1/res)][::-1]
Z=np.nan_to_num(sub,nan=0)
gy,gx=np.gradient(Z,res)
L=np.array([-1,1,1.5]);L/=np.linalg.norm(L)
n=np.dstack([-gx,gy,np.ones_like(Z)]); n/=np.linalg.norm(n,axis=2,keepdims=True)
sh=np.clip((n*L).sum(2),0,1)
lo,hi=np.nanmin(sub),np.nanmax(sub)
hn=(Z-lo)/(hi-lo+1e-9)
g=(0.3+0.7*sh)*(0.3+0.7*hn)
img=np.dstack([g*230,g*203,g*160]).astype(np.uint8); img[~np.isfinite(sub)]=255
sc=4
im=Image.fromarray(img).resize((img.shape[1]*sc,img.shape[0]*sc),Image.NEAREST); d=ImageDraw.Draw(im)
h=im.size[1]
for x in np.arange(np.ceil(x0),x1,1):
    c=(0,0,255) if x%5==0 else (120,180,255)
    X=(x-x0)/res*sc; d.line([(X,0),(X,h)],fill=c,width=1)
    if x%5==0: d.text((X+2,2),str(int(x)),fill=(0,0,255))
for y in np.arange(np.ceil(y0),y1,1):
    c=(0,0,255) if y%5==0 else (120,180,255)
    Y=h-(y-y0)/res*sc; d.line([(0,Y),(im.size[0],Y)],fill=c,width=1)
    if y%5==0: d.text((2,Y+2),str(int(y)),fill=(0,0,255))
im.save(fn)
# print height stats grid every 1mm
