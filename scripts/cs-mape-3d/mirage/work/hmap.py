import trimesh, numpy as np, sys
from PIL import Image
sys.path.insert(0,'.')
from raster import heightmap
for f in sys.argv[1:]:
    m=trimesh.load(f,process=False)
    b=m.bounds; res=0.25
    W=int((b[1,0]-b[0,0])/res)+1; H=int((b[1,1]-b[0,1])/res)+1
    z,_=heightmap(m.vertices,m.faces,b[0,0],b[0,1],res,W,H)
    np.save(f+'.npy',z)
    img=np.where(np.isnan(z),0,40+215*(z-np.nanmin(z))/(np.nanmax(z)-np.nanmin(z)+1e-9))
    Image.fromarray(img[::-1].astype(np.uint8)).save(f+'.png')
    print(f,W,H,np.nanmin(z),np.nanmax(z))
