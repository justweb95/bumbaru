import numpy as np, trimesh, manifold3d as mf, pickle
from scipy import ndimage as nd
from skimage import measure
from shapely.geometry import Polygon, MultiPolygon, LineString, box
from shapely.ops import unary_union
from shapely import affinity
from PIL import Image, ImageDraw, ImageFont
from functools import reduce
H=np.load('Hf.npy'); r=0.1
base=trimesh.load('base.ply',process=False)
B=mf.Manifold(mf.Mesh(vert_properties=np.asarray(base.vertices,np.float32),tri_verts=np.asarray(base.faces,np.uint32)))

def mask2poly(mask, ox=0, oy=0, simp=0.03):
    m=np.pad(mask,1).astype(float)
    polys=[]
    for c in measure.find_contours(m,0.5):
        if len(c)<4: continue
        p=Polygon(np.c_[(c[:,1]-1+0.5)*r+ox,(c[:,0]-1+0.5)*r+oy]).buffer(0)
        if p.area>0.01: polys.append(p)
    if not polys: return Polygon()
    g=reduce(lambda a,b:a.symmetric_difference(b),polys)
    return g.simplify(simp).buffer(0)

def geoms(g):
    if g.is_empty: return []
    return list(g.geoms) if hasattr(g,'geoms') else [g]

def extrude(g,z0,z1):
    out=[]
    for p in geoms(g):
        if p.geom_type!='Polygon' or p.area<0.05: continue
        p=p.buffer(0)
        for q in geoms(p):
            rings=[np.asarray(q.exterior.coords)[:-1]]+[np.asarray(i.coords)[:-1] for i in q.interiors]
            cs=mf.CrossSection(rings,mf.FillRule.EvenOdd)
            out.append(mf.Manifold.extrude(cs,z1-z0).translate((0,0,z0)))
    return reduce(lambda a,b:a+b,out) if out else None

def Hat(x,y): return H[int(y/r),int(x/r)]


# ---- CS2 rez: ukloni 1.6 teren gore levo
from shapely.geometry import Polygon as SP
F=mask2poly(np.isfinite(H),0,0,simp=0.02)
R=unary_union([box(22.2,133.2,85,170), box(34.5,129.5,85,133.2), box(-5,147,22.2,170), box(-5,88,9.5,170), box(74.9,121.2,85,133.2)])
WALLW=2.0; WALLH=16.3
band=(F.difference(R)).intersection(R.buffer(WALLW,join_style=2)).buffer(0)
band=unary_union([q for q in geoms(band) if q.area>0.5])
B=B-extrude(R,-1,40)+extrude(band,0,WALLH)
B=B-extrude(box(9.4,86,12.2,103.5),16.35,40)

# poravnaj ostatak padine u prolazu B-CT na profil poda te prostorije (uzet sa y=114)
Q=SP([(38.7,122.4),(44.0,122.4),(44.0,120.8),(52.5,120.8),(56.5,122.6),(61.5,119.4),(65.5,118.2),(72.2,118.0),(72.2,127.6),(38.7,127.6)])
def hfield(xs,ys,Z):
    nx,ny=len(xs),len(ys); X,Y=np.meshgrid(xs,ys,indexing='ij')
    top=np.c_[X.ravel(),Y.ravel(),Z.ravel()]; bot=np.c_[X.ravel(),Y.ravel(),np.zeros(X.size)]
    V=np.vstack([top,bot]); idx=lambda i,j:i*ny+j; N=nx*ny; F=[]
    for i in range(nx-1):
        for j in range(ny-1):
            a,b_,c,d=idx(i,j),idx(i+1,j),idx(i+1,j+1),idx(i,j+1)
            F+= [(a,b_,c),(a,c,d),(a+N,c+N,b_+N),(a+N,d+N,c+N)]
    ring=[idx(i,0) for i in range(nx)]+[idx(nx-1,j) for j in range(1,ny)]+[idx(i,ny-1) for i in range(nx-2,-1,-1)]+[idx(0,j) for j in range(ny-2,0,-1)]
    for k in range(len(ring)):
        p,q=ring[k],ring[(k+1)%len(ring)]
        F+= [(p,p+N,q),(q,p+N,q+N)]
    F=np.array(F,np.uint32)
    m=mf.Manifold(mf.Mesh(vert_properties=V.astype(np.float32),tri_verts=F))
    if m.volume()<0 or m.status()!=mf.Error.NoError:
        m=mf.Manifold(mf.Mesh(vert_properties=V.astype(np.float32),tri_verts=F[:,::-1].copy()))
    return m
xs=np.arange(38.5,72.51,0.25); ys=np.arange(115.5,128.01,0.5)
f=[];cs=[]
yfit=np.arange(112.5,116.6,0.25)
for x in xs:
    xc=min(max(x,39.6),71.4); v=np.array([H[int(y/r),int(xc/r)] for y in yfit]); ok=np.isfinite(v)&(v<15)
    p=None
    for it in range(3):
        if ok.sum()<4: break
        p=np.polyfit(yfit[ok],v[ok],1); res_=v-np.polyval(p,yfit); ok=ok&(np.abs(res_)<0.12)
    if p is None: f.append(np.nan); cs.append(np.nan)
    else: f.append(np.polyval(p,114)); cs.append(p[0])
f=np.array(f); cs=np.array(cs)
good=np.isfinite(f); print('kolone bez fita',(~good).sum())
f=np.interp(xs,xs[good],f[good]); cs=np.interp(xs,xs[good],cs[good]); print('nagib y',cs.min(),cs.max())
Z=f[:,None]+cs[:,None]*(ys[None,:]-114)
slab=hfield(xs,ys,Z); print('profil',f.min(),f.max(),'hf',slab.status(),slab.volume())
B=B-extrude(Q,-1,40)+(slab^extrude(Q,-1,40))
print('ravnanje ok',B.status())

def flatten(poly):
    global B
    B=B-extrude(poly,-1,40)+(slab^extrude(poly,-1,40))
# (7) kutija u prolazu B-CT oznacena X -> brisi (pod po profilu)
flatten(box(49.2,116.0,53.6,120.7))
# (7) kutija oznacena zelenim krugom -> pomeri gore do zida (zid pocinje na y=127.5)
Pb=box(39.2,117.6,43.7,122.35)
G=B^extrude(Pb,8.7,40)
flatten(Pb)
dy=127.45-122.35
newP=affinity.translate(Pb,0,dy)
xs2=np.arange(39.2,43.71,0.25); fl=[np.interp(x,xs,f)+np.interp(x,xs,cs)*(y-114) for x in xs2 for y in (newP.bounds[1],newP.bounds[3])]
zmin=min(fl); dz=(zmin-0.3)-8.7
G=G.translate((0,dy,dz))+extrude(newP,zmin-0.3,8.7+dz+0.01)
B=B+G
print('kutija pomerena dy',dy,'dz',round(dz,2),'novi pod',round(zmin,2),round(max(fl),2))
# (2) T spawn: obrisi zid izmedju sobe i hodnika (x~48.6-51.4, y 4.75-17.2), pod = kolona x=47.5
x0w,x1w,y0w,y1w=48.6,51.45,4.75,17.2
ysw=np.arange(y0w-0.25,y1w+0.26,0.25); xsw=np.array([x0w-0.1,x1w+0.1])
col=np.array([H[int(y/r),int(47.5/r)] for y in ysw])
Zw=np.tile(col,(2,1))
wallfill=hfield(xsw,ysw,Zw)
strip=box(x0w,y0w,x1w,y1w)
B=B-extrude(strip,-1,40)+(wallfill^extrude(strip,-1,40))
for y in np.arange(y0w,y1w,r):
    H[int(y/r),int(x0w/r):int(x1w/r)+1]=H[int(y/r),int(47.5/r)]
print('zid T obrisan', col.min(), col.max(), B.status())

# (v4) gornji zid desnog dela sobe B-CT spusten: unutrasnja ivica y=121.5 za x>=51.3
R3=box(51.3,123.5,80,140)
band3=(F.difference(R).difference(R3)).intersection(R3.buffer(WALLW,join_style=2)).buffer(0)
band3=unary_union([q for q in geoms(band3) if q.area>0.5])
B=B-extrude(R3,-1,40)+extrude(band3,0,WALLH)
print('v4 zid', band3.bounds, B.status())

print('cut ok, band area',round(band.area,1), 'status',B.status())

# ---- floor mask
Hn=np.nan_to_num(H,nan=99)
rng=nd.maximum_filter(Hn,7)-nd.minimum_filter(Hn,7)
floor=(rng<0.35)&(Hn<15.5)

def zone_grid(zone, lines_w=0.8, sp=3.0, h=0.5, emb=0.3, erode_mm=0.5):
    x0,y0,x1,y1=zone.bounds
    i0,i1,j0,j1=int(x0/r),int(x1/r)+1,int(y0/r),int(y1/r)+1
    sub=floor[j0:j1,i0:i1]; Hs=H[j0:j1,i0:i1]
    lab,n=nd.label(sub)
    # hatch
    L=max(x1-x0,y1-y0)*2
    hl=[]
    cx,cy=(x0+x1)/2,(y0+y1)/2
    for k in np.arange(-L,L,sp):
        hl.append(LineString([(cx+k-L,cy-L),(cx+k+L,cy+L)]))
        hl.append(LineString([(cx+k-L,cy+L),(cx+k+L,cy-L)]))
    hatch=unary_union([l.buffer(lines_w/2,cap_style=2) for l in hl])
    frame=zone  # outline border along zone edge too
    parts=[]; info=[]
    for c in range(1,n+1):
        mk=lab==c
        if mk.sum()*r*r<4: continue
        z=float(np.median(Hs[mk]))
        P=mask2poly(mk,i0*r,j0*r).buffer(-erode_mm).intersection(zone)
        if P.area<20: continue
        border=P.difference(P.buffer(-lines_w))
        g=hatch.intersection(P).union(border)
        g=unary_union([q for q in geoms(g.buffer(0)) if q.area>0.4])
        bx=P.bounds; gx=np.arange(bx[0]-0.5,bx[2]+0.51,0.25); gy=np.arange(bx[1]-0.5,bx[3]+0.51,0.25)
        Hc=np.nan_to_num(H,nan=0.0)
        # visina poda: median filter preko maske (zidovi van maske se ne uzimaju)
        Hm=np.where(floor,Hc,np.nan)
        Zg=np.array([[np.nanmedian(Hm[max(int(y/r)-2,0):int(y/r)+3,max(int(x/r)-2,0):int(x/r)+3]) if np.isfinite(Hm[max(int(y/r)-2,0):int(y/r)+3,max(int(x/r)-2,0):int(x/r)+3]).any() else z for y in gy] for x in gx])
        shell=hfield(gx,gy,Zg+h)-hfield(gx,gy,np.maximum(Zg-emb,0.05))
        e=extrude(g,-1,40)
        if e: e=e^shell; parts.append(e); info.append((round(z,2),round(P.area,1)))
    return reduce(lambda a,b:a+b,parts), info

def letter(ch, cx, cy, capmm=7.0, h=0.7, emb=0.3):
    font=ImageFont.truetype('/var/www/html/bumbaru/wordpress/plugins/bumbaru-3d/assets/gen/Montserrat-ExtraBold.ttf',400)
    im=Image.new('L',(600,600),0); d=ImageDraw.Draw(im); d.text((100,50),ch,font=font,fill=255)
    a=np.array(im)>127; a=a[::-1]
    ys,xs=np.nonzero(a); sc=capmm/(ys.max()-ys.min()+1)
    P=mask2poly(a,0,0,simp=0.02)
    P=affinity.scale(P,sc/r,sc/r,origin=(0,0))
    b=P.bounds; P=affinity.translate(P,cx-(b[0]+b[2])/2,cy-(b[1]+b[3])/2)
    z=float(Hat(cx,cy))
    return extrude(P,z-emb,z+h), P, z

def crate(win, floorz, thr=0.3):
    x0,y0,x1,y1=win
    i0,i1,j0,j1=int(x0/r),int(x1/r),int(y0/r),int(y1/r)
    mk=H[j0:j1,i0:i1]>floorz+thr
    mk=nd.binary_opening(mk,iterations=1)
    P=mask2poly(mk,i0*r,j0*r).buffer(0.06,join_style=2)
    return extrude(P,floorz+0.001,40)

parts={}
# letters
eA,PA,zA=letter('A',108.3,121.5,capmm=6.6); eB,PB,zB=letter('B',20.4,126.0,capmm=6.6)
print('A z',zA,PA.bounds,'B z',zB,PB.bounds)
for nm,PP in (('A',PA),('B',PB)):
    b=PP.bounds; sub=H[int(b[1]/r)-3:int(b[3]/r)+3,int(b[0]/r)-3:int(b[2]/r)+3]; print(nm,'floor range under letter',np.nanmin(sub),np.nanmax(sub))
# spawns
T=box(49.7,0.6,76.1,13.7); CT=box(78.9,102.0,87.6,115.0)
gT,iT=zone_grid(T); gCT,iCT=zone_grid(CT); print('T',iT,'CT',iCT)
# zeleni akcenti
def flatcheck(nm,x0,y0,x1,y1):
    sub=H[int(y0/r):int(y1/r),int(x0/r):int(x1/r)]; print(nm,'floor',np.nanmin(sub),np.nanmax(sub))
# (3) A kutije: blok ostaje, ali BEZ (deo mape)
win=(114.4,113.0,122.0,120.4)
i0,i1,j0,j1=int(win[0]/r),int(win[2]/r),int(win[1]/r),int(win[3]/r)
plat=mask2poly(H[j0:j1,i0:i1]>=11.0,i0*r,j0*r)
boxes=mask2poly(H[j0:j1,i0:i1]>11.4,i0*r,j0*r)
hullA=boxes.convex_hull.buffer(0.3,join_style=2).intersection(plat.buffer(-0.05))
# (v4) A kutije: levo 2 braon (postojece kutije iz modela), desno 1 plava (povecana na 2.7 mm)
wl=(114.3,112.9,118.3,119.0)
i0,i1,j0,j1=int(wl[0]/r),int(wl[2]/r),int(wl[1]/r),int(wl[3]/r)
PL=mask2poly(nd.binary_opening(H[j0:j1,i0:i1]>11.4,iterations=1),i0*r,j0*r).buffer(0.06,join_style=2).intersection(plat.buffer(-0.05))
print('braon kutije',PL.bounds,round(PL.area,1))
L1=box(114.4,113.2,117.3,116.0).intersection(plat.buffer(-0.05)); L2=box(114.4,116.3,117.3,119.1)
PLall=unary_union([PL,L1,L2])
GBR=(B^extrude(PL,11.061,40))+extrude(L1,11.061,14.0)+extrude(L2,11.061,13.4)
PR=box(119.2,117.6,121.9,120.3)
GBL=extrude(PR,11.061,13.6)

# (6) Xbox zelen
flatcheck('xbox',66.3,81.0,71.5,86.2)
xb=box(66.3,81.0,71.5,86.2)
GX=(B^extrude(xb,3.781,40))+extrude(xb,3.781,7.4)
# (4) auto na Long A, tamno plav, uz desni zid (pod nagnut 9.0->10.05, dno ravno i utisnuto)
flatcheck('auto pod',134.7,100,138.95,108)
zb=8.7; ztop=10.1
body=box(134.7,100,138.95,108).buffer(-0.5).buffer(0.5)
cab=box(135.3,102.3,138.35,106.6).buffer(-0.4).buffer(0.4)
GC=extrude(body,zb,ztop+1.6)+extrude(cab,ztop+1.5,ztop+2.9)
# (5) mala kutija dole desno od A -> tamno plava
w5=(103.5,70.5,111.0,77.5)
i0,i1,j0,j1=int(w5[0]/r),int(w5[2]/r),int(w5[1]/r),int(w5[3]/r)
sub=H[j0:j1,i0:i1]; vals,cnt=np.unique(np.round(sub[np.isfinite(sub)],2),return_counts=True); fl5=float(vals[np.argmax(cnt)])
mk5=nd.binary_opening((sub>fl5+0.3)&(sub<fl5+6),iterations=1)
lab5,n5=nd.label(mk5); sizes=nd.sum(mk5,lab5,range(1,n5+1)); print('kutija5 pod',fl5,'komponente',sizes*r*r)
k5=int(np.argmax(sizes))+1
P5=mask2poly(lab5==k5,i0*r,j0*r).buffer(0.06,join_style=2); print('kutija5',P5.bounds)
G5=B^extrude(P5,fl5+0.001,40)
C=extrude(xb,3.781,40)+extrude(PR,11.061,40)+extrude(body,zb,40)+extrude(P5,fl5+0.001,40)
Cpart=GX
red=eA+eB+gT
blue=gCT+GC+G5+GBL
Bnew=B+GBR-C-red-blue
out={'mapa (bez)':(Bnew,1),'A_B_slova_T_spawn (crvena)':(red,2),'CT_spawn_auto_kutije (plava)':(blue,3),'xbox (zelena)':(Cpart,4)}
allm=reduce(lambda a,b:a+b,[m for m,e in out.values()])
bb=allm.bounding_box(); SC=160.0/max(bb[3]-bb[0],bb[4]-bb[1]); print('rescale',SC)
out={k:(m.translate((-bb[0],-bb[1],0)).scale((SC,SC,SC)),e) for k,(m,e) in out.items()}
res={}
for k,(m,ext) in out.items():
    me=m.to_mesh(); v=np.asarray(me.vert_properties)[:,:3]; f=np.asarray(me.tri_verts)
    print(k, 'vol cm3',round(m.volume()/1000,2), 'tris',len(f), 'status',m.status())
    res[k]=(v,f,ext)
pickle.dump(res,open('parts.pkl','wb'))
