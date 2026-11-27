import numpy as np,sys,time
sys.path.insert(0,sys.argv[1])
from raster import heightmap
d=np.load(sys.argv[1]+'/obj10.npz'); V=d['v3'];F=d['f3']
z=V[:,2]; print(np.histogram(z,bins=25))
t=time.time()
res=0.2; x0,y0=-90,-98; W,H=int(181/res),int(196/res)
Z=heightmap(V,F,res,x0,y0,W,H)
print(time.time()-t)
np.save(sys.argv[1]+'/hm02.npy',Z)
m=np.isfinite(Z); print(m.mean(), np.histogram(Z[m],bins=30))
from PIL import Image
im=np.zeros(Z.shape); zz=Z[m]; im[m]=(zz-zz.min())/(zz.max()-zz.min())*235+20
Image.fromarray(im[::-1].astype(np.uint8)).save(sys.argv[1]+'/hm.png')
