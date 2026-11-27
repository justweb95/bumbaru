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

# ---- floor mask
Hn=np.nan_to_num(H,nan=99)
rng=nd.maximum_filter(Hn,7)-nd.minimum_filter(Hn,7)
floor=(rng<0.02)&(Hn<15.5)

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
        e=extrude(g,z-emb,z+h)
        if e: parts.append(e); info.append((round(z,2),round(P.area,1)))
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
eA,PA,zA=letter('A',115.0,121.6,capmm=6.6); eB,PB,zB=letter('B',20.4,126.0,capmm=6.6)
print('A z',zA,PA.bounds,'B z',zB,PB.bounds)
for nm,PP in (('A',PA),('B',PB)):
    b=PP.bounds; sub=H[int(b[1]/r)-3:int(b[3]/r)+3,int(b[0]/r)-3:int(b[2]/r)+3]; print(nm,'floor range under letter',np.nanmin(sub),np.nanmax(sub))
# spawns
T=box(8,4,70,24); CT=box(80,102,99,127)
gT,iT=zone_grid(T); gCT,iCT=zone_grid(CT); print('T',iT,'CT',iCT)
# crates
cr=[crate((119.2,117.6,121.9,120.3),11.06), crate((114.5,114.3,118.1,119.0),11.06),
    crate((67.9,81.2,71.35,85.5),3.78),
    crate((26.8,118.7,29.6,121.6),7.94), crate((22.2,114.6,26.0,118.8),8.98)]
cr=[c for c in cr if c is not None]
C=reduce(lambda a,b:a+b,cr)
Cpart=B^C   # intersection
red=eA+eB+gT
blue=gCT
Bnew=B-C-red-blue
out={'mapa (bez)':(Bnew,1),'A_B_slova_T_spawn (crvena)':(red,2),'CT_spawn (plava)':(blue,3),'kutije_xbox (braon)':(Cpart,4)}
res={}
for k,(m,ext) in out.items():
    me=m.to_mesh(); v=np.asarray(me.vert_properties)[:,:3]; f=np.asarray(me.tri_verts)
    print(k, 'vol cm3',round(m.volume()/1000,2), 'tris',len(f), 'status',m.status())
    res[k]=(v,f,ext)
pickle.dump(res,open('parts.pkl','wb'))
