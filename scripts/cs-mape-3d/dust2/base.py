import numpy as np, trimesh, manifold3d as mf
m=trimesh.load('orig.ply',process=False)
cc=m.split(only_watertight=False); m=max(cc,key=lambda c:len(c.faces))
m.merge_vertices()
print('watertight',m.is_watertight,'winding',m.is_winding_consistent, 'vol',m.volume)
S=160/m.extents[1]
m.apply_scale(S); m.apply_translation(-m.bounds[0])
print('scale',S,'extents',m.extents,'vol cm3',m.volume/1000)
M=mf.Manifold(mf.Mesh(vert_properties=np.asarray(m.vertices,np.float32),tri_verts=np.asarray(m.faces,np.uint32)))
print('manifold status',M.status(), M.volume()/1000)
m.export('base.ply'); np.save('scale.npy',S)
