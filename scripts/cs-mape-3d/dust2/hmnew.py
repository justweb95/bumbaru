import numpy as np, pickle, sys
sys.path.insert(0,'.')
from rast import zbuf
P=pickle.load(open('parts.pkl','rb'))
T=np.concatenate([v[f] for v,f,e in P.values()])
r=0.1; W=1420; Hh=1610
Z,_=zbuf(T,0,0,r,W,Hh,mode='max'); Z[~np.isfinite(Z)]=np.nan
np.save('Hf2.npy',Z); print(np.nanmin(T.reshape(-1,3),0) if False else T.reshape(-1,3).min(0), T.reshape(-1,3).max(0))
