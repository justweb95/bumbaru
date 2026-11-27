import numpy as np
def heightmap(V,F,res,x0,y0,W,H,mode='max'):
    """z-buffer top-down. returns HxW array, -inf where empty"""
    Z=np.full(H*W,-np.inf)
    T=V[F]  # n,3,3
    px=(T[:,:,0]-x0)/res; py=(T[:,:,1]-y0)/res
    mnx=np.floor(px.min(1)).astype(int); mxx=np.ceil(px.max(1)).astype(int)
    mny=np.floor(py.min(1)).astype(int); mxy=np.ceil(py.max(1)).astype(int)
    size=np.maximum(mxx-mnx,mxy-mny)+1
    for s in np.unique(size):
        idx=np.where(size==s)[0]
        # chunk
        step=max(1,int(4e7//(s*s)))
        for c in range(0,len(idx),step):
            ii=idx[c:c+step]
            gx=mnx[ii][:,None]+np.arange(s)[None,:]
            gy=mny[ii][:,None]+np.arange(s)[None,:]
            X=(gx[:,None,:]+0.5).repeat(s,1).reshape(len(ii),-1)
            Y=(gy[:,:,None]+0.5).repeat(s,2).reshape(len(ii),-1)
            ax,ay=px[ii,0][:,None],py[ii,0][:,None]
            bx,by=px[ii,1][:,None],py[ii,1][:,None]
            cx,cy=px[ii,2][:,None],py[ii,2][:,None]
            d=(by-cy)*(ax-cx)+(cx-bx)*(ay-cy)
            ok_d=np.abs(d)>1e-12
            d=np.where(ok_d,d,1)
            l1=((by-cy)*(X-cx)+(cx-bx)*(Y-cy))/d
            l2=((cy-ay)*(X-cx)+(ax-cx)*(Y-cy))/d
            l3=1-l1-l2
            e=-1e-6
            inside=(l1>=e)&(l2>=e)&(l3>=e)&ok_d
            zz=l1*T[ii,0,2][:,None]+l2*T[ii,1,2][:,None]+l3*T[ii,2,2][:,None]
            Xi=np.floor(X).astype(int); Yi=np.floor(Y).astype(int)
            inside&=(Xi>=0)&(Xi<W)&(Yi>=0)&(Yi<H)
            lin=(Yi*W+Xi)[inside]
            if mode=='max': np.maximum.at(Z,lin,zz[inside])
            else: np.minimum.at(Z,lin,zz[inside])
    return Z.reshape(H,W)
