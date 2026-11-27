import numpy as np, manifold3d as m3, time, trimesh
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/work/'
d=np.load(W+'obj10.npz'); V=d['v3'];F=d['f3']
t=time.time()
tm=trimesh.Trimesh(V,F,process=True)
print('trimesh merged', len(tm.vertices), len(tm.faces), 'watertight', tm.is_watertight, 'vol', tm.volume, time.time()-t)
mesh=m3.Mesh(vert_properties=np.asarray(tm.vertices,np.float32), tri_verts=np.asarray(tm.faces,np.uint32))
M=m3.Manifold(mesh); print('status',M.status(), M.num_tri(), M.volume(), 'genus', M.genus(), time.time()-t)
parts=M.decompose(); print('components',len(parts), sorted([p.volume() for p in parts])[-5:], sorted([p.volume() for p in parts])[:5])
for eps in [0.01,0.03,0.05]:
    t=time.time(); S=M.simplify(eps); print(eps, S.num_tri(), S.volume(), time.time()-t)
