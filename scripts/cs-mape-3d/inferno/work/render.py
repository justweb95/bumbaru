import numpy as np
from scipy import ndimage as nd
from PIL import Image
def render(Z, ID, colors, res, out, light=(-0.55,-0.65,1.0)):
    m=np.isfinite(Z)
    Zf=np.where(m,Z,0.0)
    gy,gx=np.gradient(Zf,res)
    n=np.stack([-gx,-gy,np.ones_like(Zf)],-1); n/=np.linalg.norm(n,axis=-1,keepdims=True)
    L=np.array(light,float); L/=np.linalg.norm(L)
    lam=np.clip((n*L).sum(-1),0,1)
    # shadows: march toward light
    sh=np.zeros(Z.shape,bool)
    dxy=L[:2]/np.linalg.norm(L[:2]); slope=L[2]/np.linalg.norm(L[:2])
    for d in range(1,160,1):
        dist=d*res*0.5
        ox=dxy[0]*dist/res; oy=dxy[1]*dist/res
        Zs=nd.shift(Zf,(-oy,-ox),order=0,mode='constant',cval=0)
        sh|=Zs>Zf+dist*slope+0.05
    shadow=np.where(sh,0.55,1.0)
    shadow=nd.gaussian_filter(shadow,1.2)
    ao=np.ones_like(Zf)
    for r,w in [(4,0.05),(12,0.04),(30,0.03)]:
        b=nd.gaussian_filter(Zf,r)
        ao-=w*np.clip(b-Zf,0,8)
    ao=np.clip(ao,0.45,1)
    # height tint: slightly darker low
    hz=0.85+0.15*(Zf-Zf[m].min())/(Zf[m].max()-Zf[m].min())
    base=np.zeros(Z.shape+(3,))
    for i,c in colors.items():
        c=np.array([int(c[1:3],16),int(c[3:5],16),int(c[5:7],16)])/255.
        base[ID==i]=c
    shade=(0.38+0.62*lam*shadow)*ao*hz
    spec=np.clip((n*np.array([0,0,1.])).sum(-1),0,1)**40*0.06
    rgb=np.clip(base*shade[...,None]+spec[...,None],0,1)
    alpha=m.astype(float)
    alpha=nd.gaussian_filter(alpha,0.6)*m + 0  # keep crisp-ish
    rgb[~m]=0
    img=np.dstack([rgb*255,np.clip(m*255,0,255)]).astype(np.uint8)[::-1]
    Image.fromarray(img,'RGBA').save(out)
