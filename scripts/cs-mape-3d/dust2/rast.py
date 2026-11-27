import numpy as np
def zbuf(tris, x0, y0, res, W, Hh, ids=None, mode='max'):
    """tris: (n,3,3). returns Z (Hh,W) and owner id array"""
    Z=np.full((Hh,W), -np.inf if mode=='max' else np.inf)
    O=np.full((Hh,W),-1,int)
    for k,t in enumerate(tris):
        px=(t[:,0]-x0)/res-0.5; py=(t[:,1]-y0)/res-0.5
        i0=max(int(np.floor(px.min())),0); i1=min(int(np.ceil(px.max())),W-1)
        j0=max(int(np.floor(py.min())),0); j1=min(int(np.ceil(py.max())),Hh-1)
        if i1<i0 or j1<j0: continue
        I,J=np.meshgrid(np.arange(i0,i1+1),np.arange(j0,j1+1))
        x1_,y1_=px[0],py[0]; x2,y2=px[1],py[1]; x3,y3=px[2],py[2]
        den=(y2-y3)*(x1_-x3)+(x3-x2)*(y1_-y3)
        if abs(den)<1e-12: continue
        a=((y2-y3)*(I-x3)+(x3-x2)*(J-y3))/den
        b=((y3-y1_)*(I-x3)+(x1_-x3)*(J-y3))/den
        c=1-a-b
        e=-1e-6
        ins=(a>=e)&(b>=e)&(c>=e)
        if not ins.any(): continue
        z=a*t[0,2]+b*t[1,2]+c*t[2,2]
        sub=Z[j0:j1+1,i0:i1+1]; so=O[j0:j1+1,i0:i1+1]
        upd=ins&((z>sub) if mode=='max' else (z<sub))
        sub[upd]=z[upd]; so[upd]=(k if ids is None else ids[k])
    return Z,O
