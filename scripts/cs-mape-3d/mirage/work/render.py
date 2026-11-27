import sys; sys.path.insert(0,'.')
import numpy as np, trimesh, pickle
from scipy import ndimage as nd
from PIL import Image
from raster import heightmap
P=pickle.load(open('parts.pkl','rb'))['ext']
COL={1:'#E6CBA0',2:'#C8102E',3:'#1B2A5C',4:'#6B4226',5:'#6B4226'}
names=list(P.keys())
Vs=[];Fs=[];ids=[];off=0
for k,nm in enumerate(names):
    t=trimesh.load(f'part_{nm}.ply',process=False)
    Vs.append(t.vertices);Fs.append(t.faces+off);ids+= [k]*len(t.faces);off+=len(t.vertices)
V=np.vstack(Vs);F=np.vstack(Fs);ids=np.array(ids)
res=float(sys.argv[1]) if len(sys.argv)>1 else 0.1
mn=V.min(0);mx=V.max(0)
pad=2.0
W=int((mx[0]-mn[0]+2*pad)/res)+1;H=int((mx[1]-mn[1]+2*pad)/res)+1
z,idm=heightmap(V,F,mn[0]-pad,mn[1]-pad,res,W,H,ids)
z=z[::-1];idm=idm[::-1]
alpha=~np.isnan(z); zf=np.where(alpha,z,0.0)
base=np.zeros((H,W,3))
for k,nm in enumerate(names):
    c=COL[P[nm]]; rgb=np.array([int(c[i:i+2],16) for i in (1,3,5)])/255
    base[idm==k]=rgb
ht=(zf-zf[alpha].min())/(np.ptp(zf[alpha])+1e-9)
shade=0.90+0.10*ht
# edge lines where height changes
zz=np.where(alpha,z,-5.0)
e=np.zeros_like(zf,bool)
for dy,dx in ((0,1),(1,0)):
    d=np.abs(zz-np.roll(np.roll(zz,-dy,0),-dx,1))>0.3
    e|=d|np.roll(np.roll(d,dy,0),dx,1)
e&=alpha
shade=np.where(e,shade*0.75,shade)
img=np.clip(base*shade[...,None],0,1)
img[~alpha]=0
out=np.dstack([img*255,alpha*255]).astype(np.uint8)
im=Image.fromarray(out,'RGBA')
bb=im.getbbox(); im=im.crop(bb)
k=1600/max(im.size)
if max(im.size)!=1600: im=im.resize((round(im.size[0]*k),round(im.size[1]*k)),Image.LANCZOS)
a=np.array(im); a[a[...,3]==0,:3]=0; im=Image.fromarray(a,'RGBA')
im.save(sys.argv[2] if len(sys.argv)>2 else 'preview.png'); print(im.size)
