import numpy as np
from scipy import ndimage as nd
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
S=np.load(W+'radar_on_model.npy').astype(int); Z=np.load(W+'hm_s.npy')
R,G,B,A=S[...,0],S[...,1],S[...,2],S[...,3]
green=(G>R+40)&(G>B+15)&(A>100)
orange=(R>G+40)&(R>B+60)&(A>100)
out={}
for nm,msk in [('green',green),('orange',orange)]:
    msk=nd.binary_closing(msk,iterations=3)
    lab,n=nd.label(msk)
    for i in range(1,n+1):
        comp=lab==i
        if comp.sum()<200: continue
        fill=nd.binary_fill_holes(comp)
        rr,cc=np.nonzero(fill)
        x=cc*0.1-81; y=rr*0.1-75
        zz=Z[fill]; zz=zz[np.isfinite(zz)]
        h,e=np.histogram(zz,bins=np.arange(0,18,0.25))
        top=np.argsort(h)[::-1][:4]
        print(nm,i,comp.sum(),fill.sum(),'x',x.min().round(1),x.max().round(1),'y',y.min().round(1),y.max().round(1),'zbins',[(round(e[t],2),h[t]) for t in top])
        out[f'{nm}{i}']=fill
np.savez_compressed(W+'zones.npz',**out)
