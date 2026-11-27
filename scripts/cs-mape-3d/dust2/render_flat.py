import numpy as np, pickle, sys
sys.path.insert(0,'.')
from rast import zbuf
from scipy import ndimage as nd
from PIL import Image
P=pickle.load(open('parts.pkl','rb'))
cols={1:(0xE6,0xCB,0xA0),2:(0xC8,0x10,0x2E),3:(0x1B,0x2A,0x5C),4:(0x2F,0x52,0x33)}
tris=[];ids=[]
for k,(v,f,e) in P.items(): tris.append(v[f]); ids+=[e]*len(f)
T=np.concatenate(tris); ids=np.array(ids)
mn=T.reshape(-1,3).min(0); mx=T.reshape(-1,3).max(0)
r=0.1; W=int(np.ceil((mx[0]-mn[0])/r)); Hh=int(np.ceil((mx[1]-mn[1])/r))
Z,O=zbuf(T,mn[0],mn[1],r,W,Hh,ids=ids,mode='max')
valid=np.isfinite(Z); Z[~valid]=0
Z=Z[::-1]; O=O[::-1]; valid=valid[::-1]
C=np.zeros(Z.shape+(3,))
for k,c in cols.items(): C[O==k]=c
# ton po visini: vrh zidova = puna boja, najnizi pod -10%
wall=np.percentile(Z[valid],98); low=Z[valid].min()
t=np.clip((Z-low)/(wall-low),0,1)
fac=0.90+0.10*t
# ivice gde se menja visina (>0.3 mm unutar susedstva)
mxf=nd.maximum_filter(np.where(valid,Z,-1e3),3); mnf=nd.minimum_filter(np.where(valid,Z,1e3),3)
edge=((mxf-Z)>0.3)|((Z-mnf)>0.3)
# linija samo na nizoj strani skoka (cista ivica zida), 1-2 px nakon skaliranja
edge=((mxf-Z)>0.3)
fac=np.where(edge,fac*0.75,fac)
# spoljna ivica mape
outer=valid & ~nd.binary_erosion(valid)
fac=np.where(outer,fac*0.75,fac)
img=np.clip(C*fac[...,None],0,255).astype(np.uint8)
img[~valid]=0
a=(valid*255).astype(np.uint8)
im=Image.fromarray(np.dstack([img,a]),'RGBA')
s=1600/max(im.size); im=im.resize((round(im.size[0]*s),round(im.size[1]*s)),Image.LANCZOS)
px=np.array(im); px[px[...,3]==0,:3]=0; im=Image.fromarray(px,'RGBA')
im.save(sys.argv[1]); print(im.size)
