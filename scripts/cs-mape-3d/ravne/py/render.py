import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np
from PIL import Image, ImageFilter
from scipy import ndimage
from zbuf import zbuffer
def hex2rgb(h): h=h.lstrip('#'); return np.array([int(h[i:i+2],16) for i in (0,2,4)],float)/255
COL={1:'#E6CBA0',2:'#C8102E',3:'#1B2A5C',4:'#2F5233',5:'#6B4226',6:'#F4F1EA'}
def render(parts, out, longest_px=1600, ss=2, light_az=135.0, light_el=52.0):
    """parts: list of (V,F,filament). top-down ortho; returns saved PNG RGBA."""
    allV=np.vstack([p[0] for p in parts]); mn=allV.min(0); mx=allV.max(0)
    ext=mx-mn; longest=max(ext[0],ext[1])
    pad=0.02*longest
    res=(longest+2*pad)/(longest_px*ss)
    W=int(np.ceil((ext[0]+2*pad)/res)); H=int(np.ceil((ext[1]+2*pad)/res))
    org=(mn[0]-pad,mn[1]-pad)
    Z=np.full((H,W),np.nan); ID=np.zeros((H,W),int)
    for V,F,fil in parts:
        z=zbuffer(V,F,res,W,H,org)
        better=~np.isnan(z)&(np.isnan(Z)|(z>Z+1e-6))
        Z[better]=z[better]; ID[better]=fil
    mask=~np.isnan(Z)
    Zf=np.where(mask,Z,0.0)
    # normals (top faces flat; edges -> bevel look via slight blur)
    Zb=ndimage.gaussian_filter(Zf,0.15/res)
    gy,gx=np.gradient(Zb,res)  # gy along rows (down = -y)
    # world: x right, y up; rows go down
    nx=-gx; ny=gy; nz=np.ones_like(Zb)
    nrm=np.sqrt(nx**2+ny**2+nz**2); nx/=nrm; ny/=nrm; nz/=nrm
    az=np.radians(light_az); el=np.radians(light_el)
    L=np.array([np.cos(el)*np.cos(az),np.cos(el)*np.sin(az),np.sin(el)])  # az 135 = from upper-left
    lam=np.clip(nx*L[0]+ny*L[1]+nz*L[2],0,1)
    # cast shadows: march toward light
    sh=np.zeros_like(Zf)
    hn=np.hypot(L[0],L[1]); dx=L[0]/hn; dy=-L[1]/hn  # unit pixel step dir (cols, rows)
    tanel=np.tan(el)
    maxh=Zf.max(); nsteps=int(maxh/tanel/res)+2
    for k in range(1,nsteps,max(1,nsteps//120)):
        sx=int(round(k*dx)); sy=int(round(k*dy))
        shifted=np.full_like(Zf,0.0)
        ys=slice(max(0,-sy),H-max(0,sy)); xs=slice(max(0,-sx),W-max(0,sx))
        yd=slice(max(0,sy),H-max(0,-sy)); xd=slice(max(0,sx),W-max(0,-sx))
        shifted[ys,xs]=Zf[yd,xd]
        sh=np.maximum(sh,shifted-(Zf+k*res*tanel))
    shadow=np.clip(sh/0.6,0,1)
    shadow=ndimage.gaussian_filter(shadow,0.25/res)
    # AO: how much lower than local neighborhood
    ao=np.zeros_like(Zf)
    for s in (0.6,1.5,3.0):
        loc=ndimage.maximum_filter(Zf,size=int(s/res)|1)
        ao+=np.clip((loc-Zf)/ (s*2.5),0,1)
    ao=ndimage.gaussian_filter(ao/3,0.3/res)
    base=np.zeros((H,W,3))
    for k,c in COL.items(): base[ID==k]=hex2rgb(c)
    # height tint: higher slightly brighter
    ht=(Zf/maxh)[...,None]
    lam0=L[2]
    light=(0.80+0.25*np.clip((lam-lam0)/max(1-lam0,1e-3),-1.0,1.0))[...,None]*(1-0.30*shadow[...,None])*(1-0.30*ao[...,None])*(0.92+0.13*ht)
    # subtle plastic noise / layer texture
    rng=np.random.default_rng(1); noise=ndimage.gaussian_filter(rng.normal(0,1,(H,W)),1.2)*0.015
    rgb=np.clip(base*light+noise[...,None],0,1)
    # outer silhouette: soft edge darkening
    alpha=mask.astype(float)
    img=np.dstack([rgb,alpha])
    im=Image.fromarray((img*255).astype(np.uint8),'RGBA')
    im=im.resize((W//ss,H//ss),Image.LANCZOS)
    # crop to alpha bbox + small margin
    bb=im.getchannel('A').getbbox(); m=6
    im=im.crop((max(0,bb[0]-m),max(0,bb[1]-m),min(im.width,bb[2]+m),min(im.height,bb[3]+m)))
    sc=longest_px/max(im.size)
    if abs(sc-1)>1e-3: im=im.resize((round(im.width*sc),round(im.height*sc)),Image.LANCZOS)
    im.save(out); return im.size

def render_flat(parts, out, longest_px=1600, ss=2, edge_px=1.5, edge_dark=0.25, floor_dark=0.10):
    allV=np.vstack([p[0] for p in parts]); mn=allV.min(0); mx=allV.max(0)
    ext=mx-mn; longest=max(ext[0],ext[1]); pad=0.02*longest
    res=(longest+2*pad)/(longest_px*ss)
    W=int(np.ceil((ext[0]+2*pad)/res)); H=int(np.ceil((ext[1]+2*pad)/res))
    org=(mn[0]-pad,mn[1]-pad)
    Z=np.full((H,W),np.nan); ID=np.zeros((H,W),int)
    for V,F,fil in parts:
        z=zbuffer(V,F,res,W,H,org)
        better=~np.isnan(z)&(np.isnan(Z)|(z>Z+1e-6))
        Z[better]=z[better]; ID[better]=fil
    mask=~np.isnan(Z); Zf=np.where(mask,Z,0.0)
    zmax=Zf.max(); zmin=Zf[mask].min()
    base=np.zeros((H,W,3))
    for k,c in COL.items(): base[ID==k]=hex2rgb(c)
    # tone by height: top = full colour, lowest = floor_dark darker
    t=np.clip((Zf-zmin)/max(zmax-zmin,1e-6),0,1)
    shade=1-floor_dark*(1-t)
    # edges where height (or part) changes
    e=np.zeros((H,W),bool)
    for dy,dx in ((0,1),(1,0)):
        a=Zf; b=np.roll(Zf,(-dy,-dx),(0,1))
        ida=ID; idb=np.roll(ID,(-dy,-dx),(0,1))
        d=(np.abs(a-b)>0.08)|(ida!=idb)
        e|=d; e|=np.roll(d,(dy,dx),(0,1))
    r=int(round(edge_px*ss/2))
    if r>0: e=ndimage.binary_dilation(e,iterations=r)
    e&=mask
    shade=np.where(e,shade*(1-edge_dark),shade)
    rgb=np.clip(base*shade[...,None],0,1)
    img=np.dstack([rgb*mask[...,None],mask.astype(float)])
    im=Image.fromarray((img*255).astype(np.uint8),'RGBA')
    im=im.resize((W//ss,H//ss),Image.LANCZOS)
    bb=im.getchannel('A').getbbox(); m=6
    im=im.crop((max(0,bb[0]-m),max(0,bb[1]-m),min(im.width,bb[2]+m),min(im.height,bb[3]+m)))
    sc=longest_px/max(im.size)
    if abs(sc-1)>1e-3: im=im.resize((round(im.width*sc),round(im.height*sc)),Image.LANCZOS)
    a=np.array(im); a[a[...,3]==0,:3]=0
    Image.fromarray(a,'RGBA').save(out); return im.size
