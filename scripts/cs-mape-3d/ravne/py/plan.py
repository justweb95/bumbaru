import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np, re, json
from PIL import Image, ImageDraw
from scipy import ndimage, optimize
from load import load_raw
from zbuf import zbuffer
R='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/radar/'
def overview(n):
    t=open(R+f'de_{n}.txt').read()
    g=lambda k: float(re.search(r'"%s"\s+"([-\d.]+)"'%k,t).group(1))
    return {k:(g(k+'_x'),g(k+'_y')) for k in ['CTSpawn','TSpawn','bombA','bombB']}
def fit(n,modelmask,res,island=None):
    """modelmask: H x W bool (row0=max y). returns f(u,v)->(x,y) mm"""
    H,W=modelmask.shape
    p=R+f'de_{n}.png'
    if not os.path.exists(p): return None
    r=np.array(Image.open(p).convert('RGBA'))[...,3]>40
    r=ndimage.binary_opening(r,iterations=2)
    ys,xs=np.nonzero(r)
    # radar bbox normalized
    u0,u1=xs.min()/1024,xs.max()/1024; v0,v1=ys.min()/1024,ys.max()/1024
    my,mx=np.nonzero(modelmask)
    x0,x1=mx.min()*res,mx.max()*res; y0,y1=(H-my.max())*res,(H-my.min())*res
    init=[(x1-x0)/(u1-u0),(y1-y0)/(v1-v0),x0-u0*(x1-x0)/(u1-u0),y1+v0*(y1-y0)/(v1-v0)]
    # model mask sampling at radar pixels
    uu,vv=np.meshgrid((np.arange(0,1024,4)+0.5)/1024,(np.arange(0,1024,4)+0.5)/1024)
    rr=r[::4,::4]
    def iou(p):
        sx,sy,tx,ty=p
        x=sx*uu+tx; y=ty-sy*vv
        i=(x/res).astype(int); j=(H-y/res).astype(int)
        ok=(i>=0)&(i<W)&(j>=0)&(j<H)
        m=np.zeros_like(rr); m[ok]=modelmask[j[ok],i[ok]]
        return -(m&rr).sum()/((m|rr).sum()+1)
    best=optimize.minimize(iou,init,method='Nelder-Mead',options={'xatol':0.05,'fatol':1e-5,'maxiter':2000})
    print('IoU init',-iou(init),'fit',-best.fun,best.x)
    sx,sy,tx,ty=best.x
    return (lambda u,v:(sx*u+tx,ty-sy*v)), r
def plan(n,scale_note=''):
    V,F,P=load_raw(n); res=0.25
    Z=zbuffer(V,F,res)
    H,W=Z.shape
    mask=~np.isnan(Z)
    fm=mask
    if n=='nuke':
        lab,k=ndimage.label(mask); sizes=ndimage.sum(mask,lab,range(1,k+1)); fm=lab==(1+int(np.argmax(sizes)))
    out=fit(n,fm,res)
    img=Image.new('RGB',(W,H),'black'); d=ImageDraw.Draw(img)
    zs=np.nan_to_num(Z); a=(40+200*zs/zs.max()).astype(np.uint8)
    img=Image.fromarray(np.dstack([a,a,a]))
    red=P=='8'
    Zr=zbuffer(V,F[red],res,W,H)
    arr=np.array(img); arr[~np.isnan(Zr)]=[255,60,60]; img=Image.fromarray(arr)
    d=ImageDraw.Draw(img)
    for x in range(0,int(W*res)+1,10):
        d.line([(x/res,0),(x/res,H)],fill=(0,90,140) if x%50 else (0,160,255)); d.text((x/res+2,2),str(x),fill=(0,200,255))
    for y in range(0,int(H*res)+1,10):
        d.line([(0,H-y/res),(W,H-y/res)],fill=(0,90,140) if y%50 else (0,160,255)); d.text((2,H-y/res-12),str(y),fill=(0,200,255))
    pts={}
    if out:
        f,r=out
        ov=overview(n)
        rgb=np.array(Image.open(R+f'de_{n}.png').convert('RGBA')).astype(int)
        mx_=rgb[...,:3].max(-1); mn_=rgb[...,:3].min(-1)
        gray=(rgb[...,3]>200)&(mx_-mn_<14)&(mx_>95)
        lab,k=ndimage.label(gray)
        boxes=[]
        for i,sl in enumerate(ndimage.find_objects(lab)):
            cnt=(lab[sl]==i+1).sum()
            if 12<=cnt<=900:
                jj,ii=np.nonzero(lab==i+1)
                bx,by=f(ii.mean()/1024,jj.mean()/1024); bw=np.ptp(ii)/1024*(f(1,0)[0]-f(0,0)[0]); bh=np.ptp(jj)/1024*(f(0,0)[1]-f(0,1)[1])
                boxes.append((round(float(bx),1),round(float(by),1),round(float(bw),1),round(float(bh),1)))
                d.rectangle([bx/res-bw/2/res,H-by/res-bh/2/res,bx/res+bw/2/res,H-by/res+bh/2/res],outline=(255,0,255),width=2)
        json.dump(boxes,open(f'../{n}_boxes.json','w'))
        print(' boxes',len(boxes))
        # radar outline
        ed=r & ~ndimage.binary_erosion(r)
        vy,ux=np.nonzero(ed)
        X,Y=f(ux/1024,vy/1024)
        for x,y in zip(X[::3],Y[::3]): d.point((x/res,H-y/res),fill=(80,255,80))
        for k,(u,v) in ov.items():
            x,y=f(u,v); pts[k]=(round(float(x),1),round(float(y),1))
            d.ellipse([x/res-8,H-y/res-8,x/res+8,H-y/res+8],outline=(255,255,0),width=3); d.text((x/res+10,H-y/res),k,fill=(255,255,0))
    img.save(f'../{n}_plan.png'); print(n,'model mm',W*res,H*res,pts)
    # letter centroids
    from scipy import ndimage as nd
    lab,k=nd.label(~np.isnan(Zr))
    for i in range(1,k+1):
        jj,ii=np.nonzero(lab==i)
        if len(jj)<30: continue
        print(' letter comp at x=%.1f y=%.1f size %.1fx%.1f'%(ii.mean()*res,(H-jj.mean())*res,np.ptp(ii)*res,np.ptp(jj)*res))
for n in sys.argv[1:]: plan(n)
