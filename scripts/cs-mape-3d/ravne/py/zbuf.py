import numpy as np
def zbuffer(V,F,res,W=None,H=None,origin=(0,0)):
    """top-down max-z raster. returns H x W array, row0 = max y. pixel centers."""
    ox,oy=origin
    if W is None:
        mx=V.max(0); W=int(np.ceil((mx[0]-ox)/res)); H=int(np.ceil((mx[1]-oy)/res))
    Z=np.full((H,W),-np.inf)
    T=V[F]
    # drop vertical tris
    for t in T:
        (x0,y0,z0),(x1,y1,z1),(x2,y2,z2)=t
        den=(y1-y2)*(x0-x2)+(x2-x1)*(y0-y2)
        if abs(den)<1e-12: continue
        i0=max(int(np.floor((min(x0,x1,x2)-ox)/res)),0); i1=min(int(np.ceil((max(x0,x1,x2)-ox)/res)),W-1)
        j0=max(int(np.floor((min(y0,y1,y2)-oy)/res)),0); j1=min(int(np.ceil((max(y0,y1,y2)-oy)/res)),H-1)
        if i1<i0 or j1<j0: continue
        xs=ox+(np.arange(i0,i1+1)+0.5)*res; ys=oy+(np.arange(j0,j1+1)+0.5)*res
        X,Y=np.meshgrid(xs,ys)
        a=((y1-y2)*(X-x2)+(x2-x1)*(Y-y2))/den
        b=((y2-y0)*(X-x2)+(x0-x2)*(Y-y2))/den
        c=1-a-b
        e=-1e-9
        ins=(a>=e)&(b>=e)&(c>=e)
        if not ins.any(): continue
        z=a*z0+b*z1+c*z2
        sub=Z[j0:j1+1,i0:i1+1]
        np.maximum(sub,np.where(ins,z,-np.inf),out=sub)
    Z[np.isinf(Z)]=np.nan
    return Z[::-1]
