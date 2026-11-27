import numpy as np
def heightmap(V,F,x0,y0,res,W,H,ids=None):
    """max-z rasterization of triangles; returns z (nan empty) and id map"""
    z=np.full((H,W),-np.inf); idm=np.full((H,W),-1,int)
    T=V[F]
    for k in range(len(F)):
        t=T[k]
        xs=(t[:,0]-x0)/res; ys=(t[:,1]-y0)/res
        i0=max(int(np.floor(xs.min())),0); i1=min(int(np.ceil(xs.max())),W-1)
        j0=max(int(np.floor(ys.min())),0); j1=min(int(np.ceil(ys.max())),H-1)
        if i1<i0 or j1<j0: continue
        gx,gy=np.meshgrid(np.arange(i0,i1+1),np.arange(j0,j1+1))
        x1,x2,x3=xs; y1,y2,y3=ys
        den=(y2-y3)*(x1-x3)+(x3-x2)*(y1-y3)
        if abs(den)<1e-12: continue
        a=((y2-y3)*(gx-x3)+(x3-x2)*(gy-y3))/den
        b=((y3-y1)*(gx-x3)+(x1-x3)*(gy-y3))/den
        c=1-a-b
        ins=(a>=-1e-6)&(b>=-1e-6)&(c>=-1e-6)
        if not ins.any(): continue
        zz=a*t[0,2]+b*t[1,2]+c*t[2,2]
        sub=z[j0:j1+1,i0:i1+1]; sid=idm[j0:j1+1,i0:i1+1]
        upd=ins&(zz>sub)
        sub[upd]=zz[upd]; sid[upd]=(k if ids is None else ids[k])
    z[np.isinf(z)]=np.nan
    return z,idm
