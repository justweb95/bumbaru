import numpy as np, manifold3d as m3, time, trimesh
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
d=np.load(W+'obj10.npz'); V=d['v3'].copy();F=d['f3']
tm=trimesh.Trimesh(V,F,process=True)
M=m3.Manifold(m3.Mesh(vert_properties=np.asarray(tm.vertices,np.float32), tri_verts=np.asarray(tm.faces,np.uint32)))
parts=M.decompose()
keep=[]
for p in parts:
    b=p.bounding_box(); print(round(p.volume(),3), [round(x,2) for x in b])
    if p.volume()>1: keep.append(p)
M=m3.Manifold.batch_boolean(keep, m3.OpType.Add) if len(keep)>1 else keep[0]
b=M.bounding_box(); print('bbox',b)
cx=(b[0]+b[3])/2; cy=(b[1]+b[4])/2; z0=b[2]
S=160/max(b[3]-b[0],b[4]-b[1])
print('scale',S)
M=M.translate((-cx,-cy,-z0)).rotate((0,0,90)).scale((S,S,S))
M=M.simplify(0.04)
b=M.bounding_box(); print('final bbox',b, M.num_tri(), M.volume(), M.status(), M.genus())
mm=M.to_mesh(); np.savez(W+'map_s.npz', v=mm.vert_properties[:,:3], f=mm.tri_verts, S=S)
