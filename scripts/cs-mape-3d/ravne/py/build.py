import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np, trimesh, json
from shapely.geometry import Polygon, MultiPolygon, box, Point, LineString
from shapely.ops import unary_union
from shapely import affinity
from PIL import Image, ImageFont, ImageDraw
from skimage import measure
from manifold3d import Manifold, CrossSection, FillRule
from load import load_raw
from geom import section
from cfg import CFG
SP='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/'
FONT='/var/www/html/bumbaru/wordpress/plugins/bumbaru-3d/assets/gen/Montserrat-ExtraBold.ttf'
def geoms(g):
    if g.is_empty: return []
    return list(g.geoms) if hasattr(g,'geoms') else [g]
def polys(g): return [p for p in geoms(g) if isinstance(p,Polygon) and p.area>1e-6]
def cs_from(g):
    cont=[]
    for p in polys(g):
        cont.append(np.array(p.exterior.coords)[:-1])
        for r in p.interiors: cont.append(np.array(r.coords)[:-1])
    return CrossSection(cont,FillRule.EvenOdd)
def extrude(g,z0,z1):
    if g.is_empty or z1<=z0: return Manifold()
    return cs_from(g).extrude(z1-z0).translate((0,0,z0))
def glyph(ch,cap_mm):
    f=ImageFont.truetype(FONT,800)
    im=Image.new('L',(1000,1100),0); d=ImageDraw.Draw(im); d.text((100,0),ch,font=f,fill=255)
    a=np.array(im)>127
    cs=measure.find_contours(np.pad(a,2).astype(float),0.5)
    res=Polygon()
    for c in cs:
        p=Polygon(np.c_[c[:,1],-c[:,0]]).buffer(0)
        res=res.symmetric_difference(p)
    res=res.simplify(0.8)
    minx,miny,maxx,maxy=res.bounds
    s=cap_mm/(maxy-miny)
    res=affinity.scale(res,s,s,origin=(0,0))
    c=res.envelope.centroid
    return affinity.translate(res,-c.x,-c.y)
def grid(region,spacing=3.0,w=0.8):
    minx,miny,maxx,maxy=region.bounds
    L=max(maxx-minx,maxy-miny)*1.5; cx,cy=(minx+maxx)/2,(miny+maxy)/2
    lines=[]
    n=int(L/spacing)+2
    for ang in (45,135):
        for k in range(-n,n+1):
            ln=LineString([(-L,k*spacing),(L,k*spacing)])
            lines.append(affinity.rotate(ln,ang,origin=(0,0)))
    g=unary_union([l.buffer(w/2,cap_style=2) for l in lines])
    g=affinity.translate(g,cx,cy)
    return g.intersection(region)
def clean(g,minw=0.75):
    return g.buffer(-minw/2,join_style=2).buffer(minw/2,join_style=2).intersection(g).buffer(0)
def mesh_of(m):
    mm=m.to_mesh(); V=np.array(mm.vert_properties)[:,:3]; F=np.array(mm.tri_verts)
    return V,F
def build(name):
    C=CFG[name]; s=C['s']; sz=C.get('sz',s)
    V,F,P=load_raw(name)
    m=trimesh.Trimesh(V,F,process=True)
    zs=np.unique(np.round(m.vertices[:,2],3))
    # letter zone from red painted top faces
    T=V[F[P=='8']]; up=np.cross(T[:,1]-T[:,0],T[:,2]-T[:,0])[:,2]
    lz=unary_union([Polygon(t[:,:2]).buffer(0.05) for t,u in zip(T,up) if abs(u)>1e-9]).buffer(0.8)
    bands=[]
    from rbands import raster_bands
    for i,(a,b,p) in enumerate(raster_bands(V,F)):
        if i>0:
            keep=[g for g in polys(p) if g.intersection(lz).area<0.6*g.area]
            p=unary_union(keep)
        bands.append([a,b,p])
    # merge identical consecutive bands
    mb=[]
    for a,b,p in bands:
        if mb and mb[-1][2].symmetric_difference(p).area<0.5: mb[-1][1]=b
        else: mb.append([a,b,p])
    # scale
    bands=[(a*sz,b*sz,affinity.scale(p,s,s,origin=(0,0))) for a,b,p in mb]
    # surfaces
    surf=[]
    for i,(a,b,p) in enumerate(bands):
        nxt=bands[i+1][2] if i+1<len(bands) else Polygon()
        surf.append((b,p.difference(nxt).buffer(0)))
    wall_top=bands[-1][1]
    floor_z=max(surf[:-1],key=lambda t:t[1].area)[0]
    print(name,'bands',[(round(a,2),round(b,2),round(p.area)) for a,b,p in bands],'floor',round(floor_z,3),'walls',round(wall_top,3))
    floors=[(z,g) for z,g in surf if z<=floor_z+1e-3]
    body=Manifold()
    for a,b,p in bands: body=body+extrude(p,a,b)
    parts=[]  # (name, manifold, colorkey)
    occupied=Polygon()
    # accents: recolor
    for ac in C.get('accents',[]):
        if ac['type']=='recolor':
            reg=ac['region'](surf,s) if callable(ac['region']) else affinity.scale(ac['region'],s,s,origin=(0,0))
            z0=ac.get('z0',floor_z)
            col=extrude(reg,z0,wall_top+1)^body
            body=body-col
            parts.append((ac['name'],col,ac['color'])); occupied=occupied.union(reg)
    for ac in C.get('accents',[]):
        if ac['type']=='trees':
            tz,tg=[(z,g) for z,g in surf if abs(z-ac['surface']*sz)<0.02][0]
            inner=tg.buffer(-(1.6+ac['d']/2+0.6))
            # pick points spread over inner region: farthest point sampling on a grid
            minx,miny,maxx,maxy=inner.bounds
            cand=[Point(x,y) for x in np.arange(minx,maxx,0.5) for y in np.arange(miny,maxy,0.5) if inner.contains(Point(x,y))]
            c0=inner.representative_point(); pts=[min(cand,key=lambda p:p.distance(c0))]
            while len(pts)<ac['n']:
                pts.append(max(cand,key=lambda p:min(p.distance(q) for q in pts)))
            # also keep away from edges: use cluster-ish natural look via slight size variation
            rng=np.random.default_rng(3)
            for i,p in enumerate(pts):
                r=ac['d']/2*(0.85+0.3*rng.random())
                parts.append((f"{ac['name']} {i+1}",extrude(p.buffer(r,16),tz,tz+ac['h']),ac['color']))
                print('  drvo',round(p.x,1),round(p.y,1))
        if ac['type']=='crate':
            x,y,w,h=ac['rect']; x*=s;y*=s
            w=max(w*s,2.6); h=max(h*s,2.6)
            r=affinity.rotate(box(x-w/2,y-h/2,x+w/2,y+h/2),ac.get('rot',0))
            fz,fg=max(floors,key=lambda t:t[1].intersection(r).area)
            if fg.buffer(-0.2).intersection(r).area<0.97*r.area: print('  WARN crate not fully on floor',ac['name'],fg.intersection(r).area/r.area)
            r=r.intersection(fg.buffer(-0.2))
            parts.append((ac['name'],extrude(r,fz,fz+ac.get('h',1.6)),ac['color'])); occupied=occupied.union(r.buffer(0.6))
    # letters
    main_floor=[g for z,g in floors if abs(z-floor_z)<1e-3][0]
    for ch,(tx,ty) in C['letters'].items():
        g0=glyph(ch,C.get('cap',8.0))
        ok=main_floor.buffer(-0.7).difference(occupied)
        best=None
        for rr in np.arange(0,12,0.5):
            for ang in np.linspace(0,2*np.pi,max(1,int(rr*4)),endpoint=False):
                dx=tx*s+rr*np.cos(ang); dy=ty*s+rr*np.sin(ang)
                g=affinity.translate(g0,dx,dy)
                if ok.contains(g): best=(g,dx,dy,rr); break
            if best: break
        if not best: raise SystemExit(f'letter {ch} no place')
        g,dx,dy,rr=best
        print(f'  letter {ch} at {dx:.1f},{dy:.1f} (pomeraj {rr:.1f} mm)')
        parts.append((f'slovo {ch}',extrude(g,floor_z,floor_z+C.get('lh',0.7)),2)); occupied=occupied.union(g.buffer(0.6))
    # spawns
    for nm,key,col in (('T spawn mrezica','T',2),('CT spawn mrezica','CT',3)):
        reg=affinity.scale(C[key],s,s,origin=(0,0)) if not isinstance(C[key],list) else unary_union([affinity.scale(b,s,s,origin=(0,0)) for b in C[key]])
        man=Manifold(); area=0
        for z,fg in floors:
            r=fg.buffer(-0.5,join_style=2).intersection(reg).difference(occupied)
            if r.area<1: continue
            g=clean(grid(r))
            area+=g.area
            man=man+extrude(g,z,z+0.5)
        print(f'  {nm}: {area:.0f} mm2')
        parts.append((nm,man,col))
    parts.insert(0,('mapa',body,1))
    return parts, dict(floor_z=floor_z,wall_top=wall_top)
