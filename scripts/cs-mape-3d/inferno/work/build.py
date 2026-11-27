import numpy as np, sys, manifold3d as m3, json
from scipy import ndimage as nd
from shapely.geometry import box, Polygon, MultiPolygon, LineString, MultiLineString, Point
from shapely.ops import unary_union
from shapely import affinity
from PIL import Image, ImageDraw, ImageFont
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
RES=0.1; X0=-81; Y0=-75
Z=np.load(W+'hm_s.npy'); FIN=np.isfinite(Z); Zf=np.where(FIN,Z,-99)
d=np.load(W+'map_s.npz')
MAP=m3.Manifold(m3.Mesh(vert_properties=d['v'].astype(np.float32),tri_verts=d['f'].astype(np.uint32)))
print('map',MAP.status(),MAP.num_tri())
def w2p(x,y): return int(round((y-Y0)/RES-0.5)), int(round((x-X0)/RES-0.5))
def mask2poly(mask,res=RES,x0=X0,y0=Y0):
    boxes=[]
    for r in np.nonzero(mask.any(1))[0]:
        row=mask[r].astype(np.int8); dd=np.diff(np.r_[0,row,0])
        st=np.nonzero(dd==1)[0]; en=np.nonzero(dd==-1)[0]
        for a,b in zip(st,en): boxes.append(box(x0+a*res,y0+r*res,x0+b*res,y0+(r+1)*res))
    return unary_union(boxes) if boxes else Polygon()
def polys(g):
    if g.is_empty: return []
    if g.geom_type=='Polygon': return [g]
    return [p for p in getattr(g,'geoms',[]) if p.geom_type=='Polygon']
def extrude(g,z0,z1):
    out=[]
    for p in polys(g):
        if p.area<0.05: continue
        p=p.buffer(0)
        cs=[np.array(p.exterior.coords)[:-1]]+[np.array(i.coords)[:-1] for i in p.interiors]
        cs=[c if True else c for c in cs]
        cs2=m3.CrossSection([c.astype(np.float64) for c in cs], m3.FillRule.EvenOdd)
        out.append(m3.Manifold.extrude(cs2,z1-z0).translate((0,0,z0)))
    if not out: return m3.Manifold()
    return m3.Manifold.batch_boolean(out,m3.OpType.Add)
# ---------- radar zones
S=np.load(W+'radar_on_model.npy').astype(int)
R,G,B,A=S[...,0],S[...,1],S[...,2],S[...,3]
green=(G>R+40)&(G>B+15)&(A>100); orange=(R>G+40)&(R>B+60)&(A>100)
def hull_of(msk,cond):
    rr,cc=np.nonzero(msk); x=cc*RES+X0; y=rr*RES+Y0; k=cond(x,y)
    from shapely.geometry import MultiPoint
    return MultiPoint(list(zip(x[k],y[k]))).convex_hull
T_zone=box(-80.5,-50.5,-64.0,-17.0); CT_zone=hull_of(green,lambda x,y:x>0)
A_zone=hull_of(orange,lambda x,y:x>20); B_zone=hull_of(orange,lambda x,y:x<20)
for n,zn in [('T',T_zone),('CT',CT_zone),('A',A_zone),('B',B_zone)]: print(n,[round(v,1) for v in zn.bounds],round(zn.area,1))
INFO={}
# ---------- floor helper
gy,gx=np.gradient(Zf,RES); GRAD=np.hypot(gx,gy)
def floor_mask_in(zone,maxz=None,tol=1.0):
    zm=np.zeros(Z.shape,bool)
    b=zone.bounds; r0,c0=w2p(b[0],b[1]); r1,c1=w2p(b[2],b[3])
    sub=mask2poly  # unused
    rr,cc=np.mgrid[r0:r1+1,c0:c1+1]
    from shapely import contains_xy
    inside=contains_xy(zone,cc*RES+X0+0.05,rr*RES+Y0+0.05)
    zm[r0:r1+1,c0:c1+1]=inside
    zz=Zf[zm&FIN]; base=np.percentile(zz,15)
    fm=zm&FIN&(Zf<base+tol)&(GRAD<0.8)
    fm=nd.binary_opening(fm,iterations=2)
    fm=nd.binary_erosion(fm,iterations=3)  # 0.3mm off walls
    return fm,base
# ---------- hatch
def hatch(bounds,spacing=3.0,w=0.8):
    x0,y0,x1,y1=bounds; L=(x1-x0)+(y1-y0)+10
    lines=[]
    for ang in (45,135):
        t=np.deg2rad(ang); dx,dy=np.cos(t),np.sin(t); nx,ny=-dy,dx
        cx,cy=(x0+x1)/2,(y0+y1)/2
        for k in np.arange(-L,L,spacing):
            px,py=cx+nx*k,cy+ny*k
            lines.append(LineString([(px-dx*L,py-dy*L),(px+dx*L,py+dy*L)]))
    return unary_union([l.buffer(w/2,cap_style=2) for l in lines])
def floor_follow(poly2d,fm,top_add,embed=0.4,binw=0.2):
    """solid on floor following heightmap in bins"""
    sols=[]; zz=Zf[fm]
    lo=np.floor(zz.min()/binw)*binw
    for b0 in np.arange(lo,zz.max()+binw,binw):
        mb=fm&(Zf>=b0)&(Zf<b0+binw)
        if mb.sum()<5: continue
        mb=nd.binary_dilation(mb,iterations=1)&fm
        pz=Zf[mb]
        g=mask2poly(mb).intersection(poly2d)
        g=unary_union([p for p in polys(g) if p.area>0.15])
        if g.is_empty: continue
        sols.append(extrude(g,pz.min()-embed,pz.max()+top_add))
    return m3.Manifold.batch_boolean(sols,m3.OpType.Add)
parts={}
for nm,zone in [('T',T_zone),('CT',CT_zone)]:
    fm,base=floor_mask_in(zone)
    fp=mask2poly(fm).buffer(-0.05).buffer(0.05)
    h=hatch(zone.bounds).intersection(fp)
    h=unary_union([p for p in polys(h) if p.area>0.4])
    sol=floor_follow(h,nd.binary_dilation(fm,iterations=2)&FIN&(Zf<base+1.0),0.5)
    parts[nm]=sol; print(nm,'hatch area',round(h.area,1),'floor base',round(base,2),sol.volume())
    INFO[nm]=dict(bounds=[round(v,1) for v in zone.bounds],floor=round(float(base),2))
# ---------- letters
FONT='/var/www/html/bumbaru/wordpress/plugins/bumbaru-3d/assets/gen/Montserrat-ExtraBold.ttf'
def glyph(ch,cap=7.0):
    pxmm=40; f=ImageFont.truetype(FONT,int(cap*pxmm*1.4))
    im=Image.new('L',(int(cap*pxmm*2.5),int(cap*pxmm*2.5)),0); ImageDraw.Draw(im).text((20,0),ch,font=f,fill=255)
    a=np.array(im)>127; rr,cc=np.nonzero(a); a=a[rr.min():rr.max()+1,cc.min():cc.max()+1][::-1]
    s=cap/a.shape[0]
    g=mask2poly(a,res=s,x0=0,y0=0).simplify(0.03)
    return affinity.translate(g,-g.bounds[2]/2,-g.bounds[3]/2)
def place_letter(ch,zone,cap):
    g=glyph(ch,cap); gw=g.bounds[2]-g.bounds[0]; gh=g.bounds[3]-g.bounds[1]
    hw=int((gw/2+0.4)/RES); hh=int((gh/2+0.4)/RES)
    mx=nd.maximum_filter(Zf,size=(2*hh+1,2*hw+1)); mn=nd.minimum_filter(Zf,size=(2*hh+1,2*hw+1))
    c=zone.centroid; r0,c0=w2p(c.x,c.y)
    from shapely import contains_xy
    for thr,bf in ((0.3,0),(0.45,0),(0.3,2),(0.45,2),(0.3,4),(0.45,4),(0.6,0),(0.6,3)):
        flat=(mx-mn<thr)&(mn>-50)&(Zf<15)
        rr,cc=np.nonzero(flat); X=cc*RES+X0+.05; Y=rr*RES+Y0+.05
        ok=contains_xy(zone.buffer(bf),X,Y)
        if ok.any(): print(ch,'flat thr',thr,'buffer',bf); break
    else: raise SystemExit('no flat for '+ch)
    dd=(X-c.x)**2+(Y-c.y)**2; dd[~ok]=1e9; i=np.argmin(dd)
    px,py=X[i],Y[i]; r,cI=rr[i],cc[i]
    fz=Zf[r-hh:r+hh+1,cI-hw:cI+hw+1]
    gg=affinity.translate(g,px,py)
    sol=extrude(gg,fz.min()-0.4,fz.max()+0.7)
    print(ch,'at',round(px,1),round(py,1),'floor',round(fz.min(),2),round(fz.max(),2),'size',round(gw,1),round(gh,1))
    INFO['letter_'+ch]=dict(x=round(float(px),1),y=round(float(py),1),floor=round(float(fz.max()),2),w=round(gw,1),h=round(gh,1))
    return sol
parts['A']=place_letter('A',A_zone,float(sys.argv[3]) if len(sys.argv)>3 else 6.0)
parts['B']=place_letter('B',B_zone,7.0)
np.save(W+'zones_info.npy',np.array([T_zone,CT_zone,A_zone,B_zone],dtype=object),allow_pickle=True)
# ---------- red roofs
top=FIN&(Zf>Zf[FIN].max()-0.3)
lab,n=nd.label(top); sz=nd.sum(top,lab,range(1,n+1))
ROOF_PTS=[tuple(map(float,p)) for p in json.loads(sys.argv[1])] if len(sys.argv)>1 else []
roofs=[]
RR,CC=np.mgrid[0:Z.shape[0],0:Z.shape[1]]; XX=CC*RES+X0+.05; YY=RR*RES+Y0+.05
for (x,y,rad) in ROOF_PTS:
    r,c=w2p(x,y); l=lab[r,c]
    if l==0: print('roof pt not on top',x,y); continue
    m=lab==l
    if rad>0: m&=(XX-x)**2+(YY-y)**2<rad*rad
    g=mask2poly(nd.binary_dilation(m,iterations=2)).simplify(0.05)
    if rad>0: g=g.intersection(Point(x,y).buffer(rad,64))
    zt=Zf[m].max()
    roofs.append(extrude(g,zt-0.8,zt+1)); print('roof',x,y,int(m.sum()*0.01),'mm2')
if roofs:
    parts['roof_prism']=m3.Manifold.batch_boolean(roofs,m3.OpType.Add)
# ---------- bushes
BUSH=json.loads(sys.argv[2]) if len(sys.argv)>2 else []
bush=[]
floorish=FIN&(GRAD<0.5)
for (x,y,r_) in BUSH:
    rad=int((1.2*r_+0.4)/RES)
    yy,xx=np.mgrid[-rad:rad+1,-rad:rad+1]; disk=(xx**2+yy**2)<=rad*rad
    mx=nd.maximum_filter(Zf,footprint=disk); mn=nd.minimum_filter(Zf,footprint=disk)
    flat=(mx-mn<0.25)&(mn>-50)&(Zf<15)
    dt=nd.distance_transform_edt(flat)*RES
    rr,cc=np.nonzero(flat&(dt<0.6)); X=cc*RES+X0+.05; Y=rr*RES+Y0+.05
    dd=(X-x)**2+(Y-y)**2; i=np.argmin(dd); px,py=X[i],Y[i]
    fz=mx[rr[i],cc[i]]; fzmin=mn[rr[i],cc[i]]
    rng=np.random.default_rng(int(abs(x*100+y*7)))
    a0=rng.uniform(0,2*np.pi)
    lobes=[(0,0,0.85*r_,1.15)]
    for j,(sc,zs) in enumerate([(0.62,0.95),(0.55,0.9)]):
        a=a0+j*2.3+rng.uniform(-0.3,0.3)
        lobes.append((np.cos(a)*0.55*r_,np.sin(a)*0.55*r_,sc*r_,zs))
    b=[]
    for ox,oy,lr,zs in lobes:
        dome=m3.Manifold.sphere(lr,40).scale((1,1,zs)).trim_by_plane((0,0,1),0).translate((px+ox,py+oy,fz))
        stem=m3.Manifold.cylinder(fz-fzmin+0.41,lr,lr,40).translate((px+ox,py+oy,fzmin-0.4))
        b.append(dome); b.append(stem)
    bush.append(m3.Manifold.batch_boolean(b,m3.OpType.Add)); print('bush',x,y,'->',round(px,1),round(py,1),round(float(fz),2),'moved',round(float(np.sqrt(dd[i])),1))
    INFO.setdefault('bushes',[]).append([round(float(px),1),round(float(py),1),r_])
if bush: parts['G']=m3.Manifold.batch_boolean(bush,m3.OpType.Add)
# ---------- crates (existing geometry on B site -> brown part)
CR=json.loads(sys.argv[4]) if len(sys.argv)>4 else []
crates={}
for (x,y,fl,grp,op) in CR:
    r,c=w2p(x,y)
    if fl<0:
        H=80; win=Zf[r-H:r+H+1,c-H:c+H+1]
        opened=nd.grey_opening(win,size=(45,45))
        rw=(win-opened>0.5)&(win>-50)
        raised=np.zeros(Z.shape,bool); raised[r-H:r+H+1,c-H:c+H+1]=rw
        fl=float(np.median(opened[H-15:H+16,H-15:H+16]))
    else:
        raised=FIN&(Zf>fl+0.5)&(Zf<16.5)
    lab2,_=nd.label(raised); l=lab2[r,c]
    if l==0:
        rr_,cc_=np.nonzero(lab2[r-20:r+21,c-20:c+21])
        if len(rr_)==0: print('crate seed not raised',x,y); continue
        k=np.argmin((rr_-20)**2+(cc_-20)**2); r,c=r-20+rr_[k],c-20+cc_[k]; l=lab2[r,c]
    cm=lab2==l
    if cm.sum()>10000: print('component too big',x,y,cm.sum()); continue
    if op: cm=nd.binary_opening(cm,iterations=op)
    lab3,_=nd.label(cm); r3=lab3[r,c]
    if r3: cm=lab3==r3
    wall=nd.binary_dilation(FIN&(Zf>=16.5),iterations=2)
    fp=nd.binary_dilation(cm,iterations=2)&~wall
    g=mask2poly(fp).simplify(0.03)
    ring=nd.binary_dilation(cm,iterations=4)&~nd.binary_dilation(cm,iterations=2)&~wall&FIN
    flmin=float(Zf[ring].min()) if ring.any() else fl
    crates.setdefault(grp,[]).append(extrude(g,min(flmin,fl)-0.3,Zf[cm].max()+1)); print('obj',grp,x,y,round(cm.sum()*0.01,1),'mm2 top',round(float(Zf[cm].max()),2),'floor',round(fl,2),round(flmin,2))
    INFO.setdefault('colored_objects',[]).append([grp,x,y,round(cm.sum()*0.01,1),round(float(Zf[cm].max()-fl),2)])
for grp,lst in crates.items(): parts[grp+'_prism']=m3.Manifold.batch_boolean(lst,m3.OpType.Add)
# ---------- booleans
cut=[parts[k] for k in parts]
allcut=m3.Manifold.batch_boolean(cut,m3.OpType.Add)
red_roof = MAP ^ parts['roof_prism'] if 'roof_prism' in parts else None
Kpart = MAP ^ parts['K_prism'] if 'K_prism' in parts else None
Fpart = MAP ^ parts['F_prism'] if 'F_prism' in parts else None
base = MAP - allcut
out={'base':base}
if red_roof is not None:
    # roof minus anything else (letters not on roofs)
    out['roof']=red_roof
if Kpart is not None: out['K']=Kpart
if Fpart is not None: out['F']=Fpart
for k in ['T','CT','A','B','G']:
    if k in parts:
        p=parts[k]
        # do not overlap each other: later ones minus earlier
        out[k]=p
# make colored marks not overlap each other
order=['A','B','T','CT','G','roof','K','F']
acc=m3.Manifold()
for k in order:
    if k in out:
        out[k]=out[k]-acc; acc=acc+out[k]
for k,v in out.items():
    print(k,v.status(),v.num_tri(),round(v.volume(),1),[round(b,2) for b in v.bounding_box()])
    mm=v.to_mesh(); np.savez(W+f'part_{k}.npz',v=mm.vert_properties[:,:3],f=mm.tri_verts)
json.dump(INFO,open(W+'info.json','w'),indent=1)
