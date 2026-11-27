import numpy as np, json
J=json.load(open('map.json')); z=np.load('hz.npy'); res=J['res']
def r2m(rx,ry): return ((rx-J['ox'])*J['sx'], J['Hmm']-(ry-J['oy'])*J['sy'])
def m2px(x,y): return int(round(x/res)), int(round(y/res))
def region(rx0,rx1,ry0,ry1):
    x0,y1=r2m(rx0,ry0); x1,y0=r2m(rx1,ry1)
    i0,j0=m2px(x0,y0); i1,j1=m2px(x1,y1)
    return slice(j0,j1),slice(i0,i1)
