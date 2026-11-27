import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np, json, zipfile, uuid, re, shutil
from build import build, mesh_of
from render import render
SP='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/'
SRC=SP+'ravne/src/'
OUT='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/'
COLHEX={1:'#E6CBA0',2:'#C8102E',3:'#1B2A5C',4:'#2F5233',5:'#6B4226',6:'#F4F1EA'}
COLNAME={1:'bez',2:'crvena',3:'tamno plava',4:'tamno zelena',5:'braon',6:'bela'}
def write_3mf(name,parts,path):
    used=[]
    for _,_,c in parts:
        if c not in used: used.append(c)
    used=sorted(used)
    slot={c:i+1 for i,c in enumerate(used)}
    N=len(used)
    objs=[]; comps=[]; cfgparts=[]
    for i,(pn,(V,F),c) in enumerate(parts):
        oid=i+1
        vs='\n'.join(f'     <vertex x="{x:.6f}" y="{y:.6f}" z="{z:.6f}"/>' for x,y,z in V)
        ts='\n'.join(f'     <triangle v1="{a}" v2="{b}" v3="{d}"/>' for a,b,d in F)
        objs.append(f'  <object id="{oid}" p:UUID="{uuid.uuid4()}" type="model">\n   <mesh>\n    <vertices>\n{vs}\n    </vertices>\n    <triangles>\n{ts}\n    </triangles>\n   </mesh>\n  </object>')
        comps.append(f'    <component p:path="/3D/Objects/object_1.model" objectid="{oid}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 0 0 0"/>')
        cfgparts.append(f'''    <part id="{oid}" subtype="normal_part">
      <metadata key="name" value="{pn}"/>
      <metadata key="matrix" value="1 0 0 0 0 1 0 0 0 0 1 0 0 0 0 1"/>
      <metadata key="extruder" value="{slot[c]}"/>
      <mesh_stat face_count="{len(F)}" edges_fixed="0" degenerate_facets="0" facets_removed="0" facets_reversed="0" backwards_edges="0"/>
    </part>''')
    allV=np.vstack([p[1][0] for p in parts]); mn=allV.min(0); mx=allV.max(0)
    cx,cy=128-(mn[0]+mx[0])/2,128-(mn[1]+mx[1])/2
    main_id=len(parts)+1
    NS='xmlns="http://schemas.microsoft.com/3dmanufacturing/core/2015/02" xmlns:BambuStudio="http://schemas.bambulab.com/package/2021" xmlns:p="http://schemas.microsoft.com/3dmanufacturing/production/2015/06" requiredextensions="p"'
    objfile=f'<?xml version="1.0" encoding="UTF-8"?>\n<model unit="millimeter" xml:lang="en-US" {NS}>\n <metadata name="BambuStudio:3mfVersion">1</metadata>\n <resources>\n'+'\n'.join(objs)+'\n </resources>\n <build/>\n</model>\n'
    mainfile=f'''<?xml version="1.0" encoding="UTF-8"?>
<model unit="millimeter" xml:lang="en-US" {NS}>
 <metadata name="Application">BambuStudio-02.07.01.62</metadata>
 <metadata name="BambuStudio:3mfVersion">1</metadata>
 <metadata name="Title">CS2 {name.upper()} - Bumbaru</metadata>
 <resources>
  <object id="{main_id}" p:UUID="{uuid.uuid4()}" type="model">
   <components>
{chr(10).join(comps)}
   </components>
  </object>
 </resources>
 <build p:UUID="{uuid.uuid4()}">
  <item objectid="{main_id}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 {cx:.4f} {cy:.4f} 0" printable="1"/>
 </build>
</model>
'''
    settings=f'''<?xml version="1.0" encoding="UTF-8"?>
<config>
  <object id="{main_id}">
    <metadata key="name" value="{name}"/>
    <metadata key="extruder" value="1"/>
{chr(10).join(cfgparts)}
  </object>
  <plate>
    <metadata key="plater_id" value="1"/>
    <metadata key="plater_name" value="{name.upper()}"/>
    <metadata key="locked" value="false"/>
    <model_instance>
      <metadata key="object_id" value="{main_id}"/>
      <metadata key="instance_id" value="0"/>
      <metadata key="identify_id" value="{100+main_id}"/>
    </model_instance>
  </plate>
  <assemble>
   <assemble_item object_id="{main_id}" instance_id="0" transform="1 0 0 0 1 0 0 0 1 {cx:.4f} {cy:.4f} 0" offset="0 0 0" />
  </assemble>
</config>
'''
    d=json.load(open(SRC+'Metadata/project_settings.config'))
    for k,v in list(d.items()):
        if isinstance(v,list) and len(v)==3: d[k]=[v[0]]*N
    d['filament_colour']=[COLHEX[c] for c in used]
    d['filament_multi_colour']=[COLHEX[c] for c in used]
    d['filament_self_index']=[str(i+1) for i in range(N)]
    d['filament_map']=['1']*N
    d['filament_dev_ams_drying_ams_limitations']=['1','0']*N
    d['different_settings_to_system']=['enable_prime_tower']+['']*(N+1)
    d['flush_volumes_matrix']=[('0' if i==j else '300') for i in range(N) for j in range(N)]
    rels='''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/Objects/object_1.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>'''
    toprels='''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/3dmodel.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>'''
    with zipfile.ZipFile(path,'w',zipfile.ZIP_DEFLATED) as z:
        z.write(SRC+'[Content_Types].xml','[Content_Types].xml')
        z.writestr('_rels/.rels',toprels)
        z.writestr('3D/3dmodel.model',mainfile)
        z.writestr('3D/_rels/3dmodel.model.rels',rels)
        z.writestr('3D/Objects/object_1.model',objfile)
        z.writestr('Metadata/model_settings.config',settings)
        z.writestr('Metadata/project_settings.config',json.dumps(d,indent=4))
        z.write(SRC+'Metadata/slice_info.config','Metadata/slice_info.config')
    return slot
def main(name):
    parts,info=build(name)
    meshes=[]
    stats=[]
    for pn,man,c in parts:
        if man.is_empty(): print('  empty',pn); continue
        V,F=mesh_of(man)
        meshes.append((pn,(V,F),c)); stats.append((pn,c,man.volume()/1000))
    allV=np.vstack([m[1][0] for m in meshes]); ext=allV.max(0)-allV.min(0)
    slot=write_3mf(name,meshes,OUT+f'3mf/{name}.3mf')
    sz=render([(V,F,c) for _,(V,F),c in meshes],OUT+f'pregled/{name}.png')
    js=dict(grams=round(grams(meshes),1),solid_cm3=round(sum(v for _,_,v in stats),2),name=name,ext=[round(float(e),2) for e in ext],parts=[(pn,c,COLNAME[c],slot[c],round(v,3)) for pn,c,v in stats],info=info,png=sz)
    json.dump(js,open(SP+f'{name}/stats.json','w'),indent=1)
    print(json.dumps(js))
def grams(meshes,infill=0.15,wall=0.84,top=0.8,bot=0.6,lh=0.2,rho=1.24):
    from manifold3d import Manifold, Mesh
    tot=0
    for pn,(V,F),c in meshes:
        m=Manifold(Mesh(vert_properties=np.asarray(V,np.float32),tri_verts=np.asarray(F,np.uint32)))
        zmin=V[:,2].min(); zmax=V[:,2].max(); z=zmin+lh/2
        while z<zmax:
            P=m.slice(z); A=P.area()
            if A>0:
                core=P.offset(-wall,join_type=__import__('manifold3d').JoinType.Miter)
                up=m.slice(z+top) if z+top<zmax else None
                dn=m.slice(z-bot) if z-bot>zmin else None
                if up is None or dn is None: ca=0
                else: ca=(core^up^dn).area()
                tot+=lh*(A-ca+infill*ca)
            z+=lh
    return tot/1000*rho

if __name__=='__main__':
    for n in sys.argv[1:]: main(n)
