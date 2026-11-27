import sys; sys.path.insert(0,'.')
import numpy as np, trimesh, manifold3d as mf
from shapely.geometry import Polygon, MultiPolygon, LineString, box, MultiPoint, Point
from shapely.ops import unary_union
from shapely import affinity
from skimage import measure
from scipy import ndimage as nd
from PIL import Image, ImageDraw, ImageFont
from common import *
H,W=z.shape
FONT='/var/www/html/bumbaru/wordpress/plugins/bumbaru-3d/assets/gen/Montserrat-ExtraBold.ttf'

def mask2poly(mask):
    mp=np.pad(mask.astype(float),1)
    cs=measure.find_contours(mp,0.5)
    g=Polygon()
    for c in cs:
        if len(c)<4: continue
        p=Polygon([((x-1)*res,(y-1)*res) for y,x in c]).buffer(0)
        g=g.symmetric_difference(p)
    return g.buffer(0).simplify(0.05)

def geoms(g):
    if g.is_empty: return []
    return list(g.geoms) if hasattr(g,'geoms') else [g]

def cross(g):
    polys=[]
    for p in geoms(g):
        if p.geom_type!='Polygon' or p.area<0.05: continue
        polys.append(list(p.exterior.coords)[:-1][::1])
        for i in p.interiors: polys.append(list(i.coords)[:-1])
    return mf.CrossSection(polys, mf.FillRule.EvenOdd)

def prism(g,z0,z1):
    return cross(g).extrude(z1-z0).translate((0,0,z0))

def tm2mf(t):
    return mf.Manifold(mf.Mesh(vert_properties=np.asarray(t.vertices,np.float32),tri_verts=np.asarray(t.faces,np.uint32)))
def mf2tm(m):
    k=m.to_mesh(); return trimesh.Trimesh(np.asarray(k.vert_properties)[:,:3],np.asarray(k.tri_verts),process=False)

def zs_in(g):
    mk=np.zeros((H,W),bool)
    img=Image.new('L',(W,H),0); d=ImageDraw.Draw(img)
    for p in geoms(g): d.polygon([(x/res,y/res) for x,y in p.exterior.coords],fill=1)
    mk=np.array(img).astype(bool)
    v=z[mk]; return v[~np.isnan(v)]

def rect_mask(rx0,rx1,ry0,ry1):
    sl=region(rx0,rx1,ry0,ry1); m=np.zeros((H,W),bool); m[sl]=True; return m

base=trimesh.load('map_scaled.ply',process=False)
MAP=tm2mf(base)
parts={}  # name -> (manifold, extruder)

# ---------- letters
def letter_poly(ch,cap):
    S=1000; f=ImageFont.truetype(FONT,S)
    img=Image.new('L',(int(S*1.2),int(S*1.4)),0); d=ImageDraw.Draw(img); d.text((50,50),ch,font=f,fill=255)
    a=np.array(img)>127
    cs=measure.find_contours(np.pad(a,1).astype(float),0.5); g=Polygon()
    for c in cs:
        g=g.symmetric_difference(Polygon([(x,-y) for y,x in c]).buffer(0))
    g=g.buffer(0).simplify(2)
    minx,miny,maxx,maxy=g.bounds; k=cap/(maxy-miny)
    g=affinity.scale(g,k,k,origin=(0,0))
    c=g.centroid; minx,miny,maxx,maxy=g.bounds
    return affinity.translate(g,-(minx+maxx)/2,-(miny+maxy)/2)

def place_letter(ch,site_rect,cap,name):
    m=rect_mask(*site_rect)
    zc=np.nanmedian(z[m])
    # floor = most common height level in site
    v=z[m]; v=v[~np.isnan(v)]; hh,e=np.histogram(v,bins=200); 
    flat=m&(~np.isnan(z))&(np.abs(z-np.nanmin(v))<1.0)
    # free space: floor not near anything higher than floor+0.3
    vals,cnt=np.unique(np.round(v,1),return_counts=True); fl=vals[np.argmax(cnt)]
    lowmask=(~np.isnan(z))&(np.abs(z-fl)<0.12)
    dist=nd.distance_transform_edt(lowmask)*res
    dist[~m]=0
    j,i=np.unravel_index(np.argmax(dist),dist.shape)
    print(name,'max clearance mm',dist[j,i],'at',i*res,j*res)
    g=letter_poly(ch,cap)
    g=affinity.translate(g,i*res,j*res)
    vz=zs_in(g.buffer(0.3))
    zb=vz.min(); zt=vz.max()
    print(name,'floor range',zb,zt)
    parts[name]=(prism(g,zb-0.4,zt+0.7),2)
    return g,(zb,zt)

# ---------- spawn grids
def spawn(rect,name,ext,pitch=3.0,w=0.8,h=0.5,border=True,tol=0.15):
    m=rect_mask(*rect)
    v=z[m]; v=v[~np.isnan(v)]
    vals,cnt=np.unique(np.round(v,1),return_counts=True); fl=vals[np.argmax(cnt)]
    floor=m&(~np.isnan(z))&(np.abs(z-fl)<tol)
    # keep largest component
    lab,n=nd.label(floor); sz=nd.sum(floor,lab,range(1,n+1)); floor=lab==(1+np.argmax(sz))
    g=mask2poly(floor).buffer(-0.6).buffer(0.2,join_style=2).buffer(-0.2,join_style=2)
    g=max(geoms(g),key=lambda p:p.area)
    minx,miny,maxx,maxy=g.bounds; D=(maxx-minx)+(maxy-miny)
    lines=[]
    step=pitch*np.sqrt(2)
    for c in np.arange(minx-D,maxx+D,step):
        lines.append(LineString([(c,miny-1),(c+D+2,miny-1+D+2)]).buffer(w/2,cap_style=2))
        lines.append(LineString([(c,maxy+1),(c+D+2,maxy+1-D-2)]).buffer(w/2,cap_style=2))
    grid=unary_union(lines).intersection(g)
    if border: grid=unary_union([grid, g.difference(g.buffer(-w))])
    grid=grid.buffer(0)
    vz=zs_in(g); print(name,'floor',fl,'range',vz.min(),vz.max(),'area',g.area)
    parts[name]=(prism(grid,fl-0.4,fl+h),ext)
    return g,grid,fl

# ---------- crates (existing geometry recolored)
def crates(rect,name,ext,minh=0.8,amin=3.0,amax=30.0):
    m=rect_mask(*rect)
    lowf=nd.minimum_filter(np.where(np.isnan(z),99,z),size=int(8/res))
    raised=m&(~np.isnan(z))&(z>lowf+minh)
    lab,n=nd.label(raised)
    out=[]
    for k in range(1,n+1):
        c=lab==k; a=c.sum()*res*res
        if a<amin or a>amax: continue
        zc=z[c]
        if zc.max()>14.5: continue   # B-site corner brackets (deo arhitekture)
        ring=nd.binary_dilation(c,iterations=6)&~nd.binary_dilation(c,iterations=2)
        rz=z[ring]; rz=rz[~np.isnan(rz)]
        fz=np.percentile(rz,10)
        if np.mean(rz<fz+0.3)<0.1: continue
        g=mask2poly(c).buffer(0.12,join_style=2)
        out.append((g,fz,zc.max()))
    print(name,'crates',len(out),[ (round(g.area,1),round(f,2)) for g,f,t in out])
    if out:
        cut=None
        for g,fz,top in out:
            p=prism(g,fz-0.3,top+1.0)
            cut=p if cut is None else cut+p
        parts[name]=('CUT',cut,ext)
    return out

info={}
info['B']=place_letter('B',(195,272,248,330),7.0,'slovo_B')
info['A']=place_letter('A',(505,610,745,820),7.0,'slovo_A')
info['T']=spawn((830,945,312,425),'T_spawn',2,tol=0.3)
info['CT']=spawn((296,358,648,778),'CT_spawn',3)
cr=[]
cr+=crates((440,650,715,870),'kutije_A',4)
cr+=crates((150,300,230,345),'kutije_B',4)
cr+=crates((665,735,435,510),'kutije_topmid',4)

# assemble: subtract additive parts from map, intersect crate cuts
final={}
body=MAP
for nm,val in list(parts.items()):
    if val[0]=='CUT':
        _,cut,ext=val
        piece=MAP^cut
        body=body-cut
        final[nm]=(piece,ext)
for nm,val in parts.items():
    if val[0]!='CUT':
        pm,ext=val
        body=body-pm
        final[nm]=(pm,ext)
final={'mapa':(body,1),**final}
import pickle
out={}
for nm,(m,ext) in final.items():
    t=mf2tm(m)
    print(nm,ext,'tris',len(t.faces),'wt',t.is_watertight,'vol',round(t.volume,1),'z',t.bounds[:,2].round(2))
    t.export(f'part_{nm}.ply'); out[nm]=ext
pickle.dump({'ext':out,'info':{k:(v[0].wkt if hasattr(v[0],'wkt') else None) for k,v in info.items()},'crates':[(g.wkt,f,t) for g,f,t in cr]},open('parts.pkl','wb'))
