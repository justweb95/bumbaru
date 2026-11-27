import sys, re, json, zipfile, time
import numpy as np
from scipy import ndimage
from PIL import Image
GOT='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/'
PAL={'#E6CBA0':(230,203,160),'#C8102E':(200,16,46),'#1B2A5C':(27,42,92),'#2F5233':(47,82,51),'#6B4226':(107,66,38),'#F4F1EA':(244,241,234)}
# ---- shared style parameters (same for every map) ----
PXMM=10; SS=2
FLOOR_DARK=0.07; SHADOW_LEN_MM=2.5
LIGHT_AZ=315; LIGHT_EL=50; SHADOW_MAX=0.14; SHADOW_BLUR_PX=3
AO_MAX=0.0; AO_SIGMA_MM=1.5; AO_RANGE_MM=4.0
EDGE_DZ=0.3; EDGE_DARK=0.18; EDGE_PX=1.25
def nearest(h):
    h=h.upper()
    if h in PAL: return PAL[h]
    c=np.array([int(h[i:i+2],16) for i in (1,3,5)])
    return min(PAL.values(),key=lambda p:((np.array(p)-c)**2).sum())
def mat(t):
    v=[float(x) for x in t.split()]
    M=np.eye(4); M[:3,:3]=np.array(v[:9]).reshape(3,3).T; M[:3,3]=v[9:12]; return M
def load3mf(path):
    z=zipfile.ZipFile(path)
    cols=json.loads(z.read('Metadata/project_settings.config'))['filament_colour']
    ms=z.read('Metadata/model_settings.config').decode()
    ext={}
    for pid,body in re.findall(r'<part id="(\d+)"[^>]*>(.*?)</part>',ms,re.S):
        m=re.search(r'key="extruder" value="(\d+)"',body); ext[int(pid)]=int(m.group(1)) if m else 1
    main=z.read('3D/3dmodel.model').decode()
    comps=re.findall(r'<component p:path="([^"]+)" objectid="(\d+)"[^>]*?transform="([^"]+)"',main)
    files={}
    parts=[]
    for p,oid,tr in comps:
        p=p.lstrip('/')
        if p not in files:
            t=z.read(p).decode(); files[p]={}
            for o,body in re.findall(r'<object id="(\d+)"[^>]*>(.*?)</object>',t,re.S):
                vs=re.findall(r'<vertex x="([^"]+)" y="([^"]+)" z="([^"]+)"',body)
                fs=re.findall(r'<triangle v1="(\d+)" v2="(\d+)" v3="(\d+)"',body)
                files[p][int(o)]=(np.array(vs,float),np.array(fs,int))
        V,F=files[p][int(oid)]
        M=mat(tr); V=V@M[:3,:3].T+M[:3,3]
        e=ext.get(int(oid),1)
        parts.append((V,F,nearest(cols[e-1])))
    return parts
def zbuffer(V,F,res,W,H,org):
    """max-z raster, pixel centers, row0 = max y; vectorized for small triangles"""
    Z=np.full(H*W,-np.inf)
    T=V[F]; ox,oy=org
    x=(T[:,:,0]-ox)/res-0.5; y=(T[:,:,1]-oy)/res-0.5   # pixel-center coords
    den=(y[:,1]-y[:,2])*(x[:,0]-x[:,2])+(x[:,2]-x[:,1])*(y[:,0]-y[:,2])
    ok=np.abs(den)>1e-9
    T,x,y,den=T[ok],x[ok],y[ok],den[ok]
    i0=np.clip(np.ceil(x.min(1)),0,W-1).astype(int); i1=np.clip(np.floor(x.max(1)),0,W-1).astype(int)
    j0=np.clip(np.ceil(y.min(1)),0,H-1).astype(int); j1=np.clip(np.floor(y.max(1)),0,H-1).astype(int)
    bw=i1-i0+1; bh=j1-j0+1
    valid=(x.max(1)>=0)&(x.min(1)<=W-1)&(y.max(1)>=0)&(y.min(1)<=H-1)&(bw>0)&(bh>0)
    small=valid&(bw<=12)&(bh<=12)
    def raster(idx,K):
        oi,oj=np.meshgrid(np.arange(K),np.arange(K)); oi=oi.ravel(); oj=oj.ravel()
        for s in range(0,len(idx),40000):
            b=idx[s:s+40000]
            I=i0[b,None]+oi[None]; J=j0[b,None]+oj[None]
            X=I.astype(float); Y=J.astype(float)
            xb,yb,db=x[b],y[b],den[b]
            a=((yb[:,1,None]-yb[:,2,None])*(X-xb[:,2,None])+(xb[:,2,None]-xb[:,1,None])*(Y-yb[:,2,None]))/db[:,None]
            c=((yb[:,2,None]-yb[:,0,None])*(X-xb[:,2,None])+(xb[:,0,None]-xb[:,2,None])*(Y-yb[:,2,None]))/db[:,None]
            d=1-a-c; e=-1e-7
            ins=(a>=e)&(c>=e)&(d>=e)&(I<=i1[b,None])&(J<=j1[b,None])
            zz=a*T[b,0,2,None]+c*T[b,1,2,None]+d*T[b,2,2,None]
            lin=(J*W+I)[ins]; np.maximum.at(Z,lin,zz[ins])
    raster(np.nonzero(small)[0],12)
    Z2=Z.reshape(H,W)
    for k in np.nonzero(valid&~small)[0]:
        X,Y=np.meshgrid(np.arange(i0[k],i1[k]+1,dtype=float),np.arange(j0[k],j1[k]+1,dtype=float))
        xb,yb,db=x[k],y[k],den[k]
        a=((yb[1]-yb[2])*(X-xb[2])+(xb[2]-xb[1])*(Y-yb[2]))/db
        c=((yb[2]-yb[0])*(X-xb[2])+(xb[0]-xb[2])*(Y-yb[2]))/db
        d=1-a-c; e=-1e-7
        ins=(a>=e)&(c>=e)&(d>=e)
        zz=np.where(ins,a*T[k,0,2]+c*T[k,1,2]+d*T[k,2,2],-np.inf)
        sub=Z2[j0[k]:j1[k]+1,i0[k]:i1[k]+1]; np.maximum(sub,zz,out=sub)
    Z2[np.isinf(Z2)]=np.nan
    return Z2[::-1]
def render(path,out):
    parts=load3mf(path)
    allV=np.vstack([p[0] for p in parts]); mn=allV.min(0); mx=allV.max(0)
    res=1.0/(PXMM*SS)
    Wf=int(round((mx[0]-mn[0])*PXMM)); Hf=int(round((mx[1]-mn[1])*PXMM))
    W=Wf*SS; H=Hf*SS
    Z=np.full((H,W),np.nan); C=np.zeros((H,W,3))
    for V,F,col in parts:
        z=zbuffer(V,F,res,W,H,(mn[0],mn[1]))
        b=~np.isnan(z)&(np.isnan(Z)|(z>Z+1e-6))
        Z[b]=z[b]; C[b]=np.array(col)/255
    mask=~np.isnan(Z); top=np.nanmax(Z)
    Zf=np.where(mask,Z,0.0)
    # 1) height tone (absolute mm below top)
    zq=np.round(Zf[mask]/0.05).astype(int); floor_z=np.bincount(zq).argmax()*0.05
    span=max(top-floor_z,0.5)
    tone=1-FLOOR_DARK*np.clip((top-Zf)/span,0,1)
    # 2) cast shadow from heightmap, light from azimuth 315 (NW, i.e. up-left in image)
    az=np.radians(LIGHT_AZ); tanel=np.tan(np.radians(LIGHT_EL))
    dxw=np.sin(az); dyw=np.cos(az)          # toward light in world (x east, y north): (-0.707,+0.707)
    dc=dxw; dr=-dyw                          # image columns / rows
    sh=np.zeros_like(Zf)
    maxd=SHADOW_LEN_MM/res/np.hypot(dc,dr)
    for k in range(1,int(maxd)+1):
        sc=int(round(k*dc)); sr=int(round(k*dr))
        sft=np.zeros_like(Zf)
        ys=slice(max(0,-sr),H-max(0,sr)); xs=slice(max(0,-sc),W-max(0,sc))
        yd=slice(max(0,sr),H-max(0,-sr)); xd=slice(max(0,sc),W-max(0,-sc))
        sft[ys,xs]=Zf[yd,xd]
        dist=k*res*np.hypot(dc,dr)
        sh=np.maximum(sh,sft-(Zf+dist*tanel))
    # shadow may not exceed SHADOW_LEN_MM regardless of wall height (loop is limited to that distance)
    shadow=(sh>0.05).astype(float)
    shadow=ndimage.gaussian_filter(shadow,SHADOW_BLUR_PX*SS/2)*mask
    # 3) smooth heightmap AO
    blur=ndimage.gaussian_filter(Zf,AO_SIGMA_MM/res)
    ao=ndimage.gaussian_filter(np.clip((blur-Zf)/AO_RANGE_MM,0,1),0.5/res)
    # 4) edge lines
    e=np.zeros((H,W),bool)
    for ax in (0,1):
        d=np.abs(np.diff(np.where(mask,Zf,np.nan),axis=ax))>EDGE_DZ
        d=np.nan_to_num(d).astype(bool)
        if ax==0: e[:-1]|=d; e[1:]|=d
        else: e[:,:-1]|=d; e[:,1:]|=d
    it=int(round(EDGE_PX*SS/2))-1   # 2 ss px (=1 final px) already from both sides of the step
    if it>0: e=ndimage.binary_dilation(e,iterations=it)   # NB: iterations=0 would flood-fill everything
    e&=mask
    lum=tone*(1-SHADOW_MAX*shadow)*(1-AO_MAX*ao)*np.where(e,1-EDGE_DARK,1.0)
    rgb=np.clip(C*lum[...,None],0,1)
    a=mask.astype(float)
    # alpha-weighted box downsample
    def ds(x): return x.reshape(Hf,SS,Wf,SS,*x.shape[2:]).mean((1,3))
    A=ds(a); RGB=ds(rgb*a[...,None])/np.maximum(A,1e-9)[...,None]
    RGB[A==0]=0
    img=np.dstack([RGB,A]); im=Image.fromarray(np.round(img*255).astype(np.uint8),'RGBA')
    im.save(out); return im.size
if __name__=='__main__':
    outdir=sys.argv[1]
    for n in sys.argv[2:]:
        t=time.time(); sz=render(GOT+f'3mf/{n}.3mf',f'{outdir}/{n}.png'); print(n,sz,round(time.time()-t,1),'s',flush=True)
