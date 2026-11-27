from shapely.geometry import box, Point
def top_in(cx,cy,r):
    return lambda surf,s: max(surf,key=lambda t:t[0])[1].intersection(Point(cx*s,cy*s).buffer(r*s))
def rim(z,w):
    return lambda surf,s: (lambda g: g.difference(g.buffer(-w)))([g for zz,g in surf if abs(zz-z)<0.02][0])
def surf_at(z):
    return lambda surf,s: [g for zz,g in surf if abs(zz-z)<0.02][0]
CFG={
 'ancient':dict(s=160/170.15, sz=1.0, cap=8.0,
   letters={'A':(34,132),'B':(137,108)},
   T=box(60,0,92,36), CT=box(70,146,99,171),
   accents=[
     dict(type='recolor',name='dzungla obod',region=rim(7.45,1.6),color=4),
     dict(type='trees',name='drvece',surface=7.45,n=6,d=3.2,h=1.2,color=4),
     dict(type='crate',name='kamen blok A (levo)',rect=(21.3,138.8,5.0,4.6),color=4,h=1.8),
     dict(type='crate',name='kamen blok A (desno)',rect=(44.8,135.5,3.3,5.8),color=4,h=1.8),
     dict(type='crate',name='kamen blok B',rect=(114.1,114.4,3.0,3.0),color=4,h=1.8),
   ]),

 'nuke':dict(s=160/209.96, sz=1.0, cap=8.0,
   letters={'A':(118,62),'B':(35,119)},
   T=box(0,36,48,66), CT=box(166,55,210,82),
   accents=[
     dict(type='recolor',name='silos',region=top_in(101,28,10),color=6),
     dict(type='crate',name='kontejner outside 1',rect=(139.8,31.6,4.5,8.8),color=6,h=2.0),
     dict(type='crate',name='kontejner outside 2',rect=(147.8,10.9,3.4,8.4),color=6,h=2.0),
   ]),
 'vertigo':dict(s=160/200.86, sz=1.0, cap=8.0,
   letters={'A':(167,76),'B':(29,180)},
   T=box(4,8,62,45), CT=box(98,160,142,198),
   accents=[
     dict(type='crate',name='sanduk A',rect=(161.0,79.0,6.5,10.9),color=5,h=1.8),
     dict(type='crate',name='sanduk B 1',rect=(21.9,169.2,6.8,6.9),color=5,h=1.8),
     dict(type='crate',name='sanduk B 2',rect=(40.1,184.1,4.5,4.6),color=5,h=1.8),
   ]),
 'cache':dict(s=160/217.36, sz=1.0, cap=8.0,
   letters={'A':(71,142),'B':(75,14)},
   T=box(165,48,217,92), CT=box(0,55,32,112),
   accents=[
     dict(type='crate',name='sanduci A (quad, priblizno)',rect=(84,138,7,7),color=5,h=1.8),
     dict(type='crate',name='sanduk B (priblizno)',rect=(64,27,5,5),color=5,h=1.8),
   ]),
}
