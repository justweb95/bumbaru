import sys, numpy as np
from PIL import Image
B=np.array([230,203,160.])
def stats(p):
    a=np.array(Image.open(p)).astype(float)
    rgb=a[...,:3]; al=a[...,3]
    # beige hue: colour proportional to filament beige (ratio check), opaque
    k=(rgb*B).sum(-1)/(B*B).sum()
    dev=np.abs(rgb-k[...,None]*B).max(-1)
    m=(al==255)&(dev<8)&(k>0.5)
    px=rgb[m]
    L=(0.2126*px[:,0]+0.7152*px[:,1]+0.0722*px[:,2]).mean()
    q=(np.round(px)).astype(int); key=q[:,0]*65536+q[:,1]*256+q[:,2]
    u,c=np.unique(key,return_counts=True); d=u[c.argmax()]
    dom=(d//65536,(d//256)%256,d%256); share=c.max()/len(key)
    return L,dom,share,m.sum()/(al==255).sum()
ns=['dust2','mirage','inferno','ancient','cache','nuke','vertigo']
res={n:stats(f'{sys.argv[1]}/{n}.png') for n in ns}
avg=np.mean([r[0] for r in res.values()])
print(f'prosek L = {avg:.1f}')
for n,(L,dom,share,frac) in res.items():
    print(f'{n:8s} L={L:6.1f}  odst={L-avg:+5.1f}  dominantna={dom} ({share*100:.0f}% bez px)  bez udeo={frac*100:.0f}%')
