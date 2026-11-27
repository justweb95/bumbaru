import numpy as np
from scipy import ndimage as nd
from PIL import Image, ImageDraw
H=np.load('Hf.npy'); res=0.1
Hn=np.nan_to_num(H,nan=0)
Ho=nd.grey_opening(Hn,size=(61,61))
D=Hn-Ho
bump=(D>0.4)&np.isfinite(H)
lab,n=nd.label(bump)
objs=nd.find_objects(lab)
out=[]
for i,sl in enumerate(objs):
    h=(sl[0].stop-sl[0].start)*res; w=(sl[1].stop-sl[1].start)*res
    area=(lab[sl]==i+1).sum()*res*res
    if area<1.5 or w>9 or h>9: continue
    cy=(sl[0].start+sl[0].stop)/2*res; cx=(sl[1].start+sl[1].stop)/2*res
    dz=D[sl][lab[sl]==i+1].max()
    out.append((i+1,round(cx,1),round(cy,1),round(w,1),round(h,1),round(area,1),round(dz,2)))
for o in out: print(o)
np.save('lab.npy',lab)
im=Image.open('annot.png').convert('RGB'); d=ImageDraw.Draw(im); Hh=H.shape[0]
for o in out:
    d.text((o[1]/res,Hh-o[2]/res),str(o[0]),fill=(255,0,255))
im.save('boxes.png')
