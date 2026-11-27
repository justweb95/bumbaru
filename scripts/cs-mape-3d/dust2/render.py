import numpy as np, pickle, sys
sys.path.insert(0,'.')
from rast import zbuf
from scipy import ndimage as nd
from PIL import Image
P=pickle.load(open('parts.pkl','rb'))
cols={1:(0xE6,0xCB,0xA0),2:(0xC8,0x10,0x2E),3:(0x1B,0x2A,0x5C),4:(0x2F,0x52,0x33),5:(0x6B,0x42,0x26)}
tris=[];ids=[]
for k,(v,f,e) in P.items(): tris.append(v[f]); ids+= [e]*len(f)
T=np.concatenate(tris); ids=np.array(ids)
mn=T.reshape(-1,3).min(0); mx=T.reshape(-1,3).max(0)
r=0.1; W=int(np.ceil((mx[0]-mn[0])/r)); Hh=int(np.ceil((mx[1]-mn[1])/r))
Z,O=zbuf(T,mn[0],mn[1],r,W,Hh,ids=ids,mode='max')
valid=np.isfinite(Z); Z[~valid]=0
Z=Z[::-1]; O=O[::-1]; valid=valid[::-1]
gy,gx=np.gradient(Z,r); gy=-gy
L=np.array([-0.55,0.55,0.63]); L/=np.linalg.norm(L)
n=np.dstack([-gx,-gy,np.ones_like(Z)]); n/=np.linalg.norm(n,axis=2,keepdims=True)
diff=np.clip((n*L).sum(2),0,1)
# cast shadow (march toward light)
sh=np.ones_like(Z)
dirx,diry=L[0],-L[1]   # image coords: y down
tanel=L[2]/np.hypot(L[0],L[1])
for s in np.arange(1,45,1):
    dx=int(round(dirx/np.hypot(dirx,diry)*s)); dy=int(round(diry/np.hypot(dirx,diry)*s))
    Zs=np.roll(np.roll(Z,-dy,0),-dx,1)
    sh=np.minimum(sh,np.where(Zs-Z>s*r*tanel+0.05,0.72,1.0))
sh=nd.gaussian_filter(sh,1.2)
# AO
ao=np.ones_like(Z)
for sig in (3,10,30):
    b=nd.gaussian_filter(Z,sig)
    ao*=np.clip(1-0.06*np.clip(b-Z,0,None),0.55,1)
C=np.zeros(Z.shape+(3,))
for k,c in cols.items(): C[O==k]=c
shade=(0.35+0.65*diff)*sh*ao*(0.92+0.08*Z/Z.max())
img=np.clip(C*shade[...,None]*1.12,0,255).astype(np.uint8)
a=(valid*255).astype(np.uint8)
im=Image.fromarray(np.dstack([img,a]),'RGBA')
s=1600/max(im.size); im=im.resize((round(im.size[0]*s),round(im.size[1]*s)),Image.LANCZOS)
im.save(sys.argv[1]); print(im.size, mx-mn)
