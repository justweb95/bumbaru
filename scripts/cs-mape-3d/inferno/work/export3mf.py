import numpy as np, json, zipfile, io, uuid, manifold3d as m3, trimesh
W='/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/inferno/'
OUT='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/3mf/inferno.3mf'
COLORS=['#E6CBA0','#C8102E','#1B2A5C','#6B4226']
# name, file, extruder
PARTS=[('Inferno mapa (bez)','base',1),('Slovo A (crvena)','A',2),('Slovo B (crvena)','B',2),
       ('T spawn mrezica (crvena)','T',2),('CT spawn mrezica (plava)','CT',3),('Sanduci na A i B sajtu (braon)','K',4),('Fontana na B (plava)','F',3)]
meshes=[]
for nm,k,e in PARTS:
    d=np.load(W+f'work/part_{k}.npz'); V=d['v'].astype(float); F=d['f'].astype(np.int64)
    t=trimesh.Trimesh(V,F,process=False)
    assert t.is_watertight, k
    meshes.append((nm,k,e,np.asarray(t.vertices),np.asarray(t.faces)))
allv=np.vstack([m[3] for m in meshes]); mn=allv.min(0); mx=allv.max(0)
ctr=np.array([(mn[0]+mx[0])/2,(mn[1]+mx[1])/2,mn[2]])
print('bbox',mn,mx,'size',mx-mn)
def mesh_xml(oid,V,F):
    V=V-ctr
    vs='\n'.join(f'     <vertex x="{x:.4f}" y="{y:.4f}" z="{z:.4f}"/>' for x,y,z in V)
    fs='\n'.join(f'     <triangle v1="{a}" v2="{b}" v3="{c}"/>' for a,b,c in F)
    return (f'  <object id="{oid}" p:UUID="{uuid.uuid4()}" type="model">\n   <mesh>\n    <vertices>\n{vs}\n    </vertices>\n'
            f'    <triangles>\n{fs}\n    </triangles>\n   </mesh>\n  </object>\n')
NS='xmlns="http://schemas.microsoft.com/3dmanufacturing/core/2015/02" xmlns:BambuStudio="http://schemas.bambulab.com/package/2021" xmlns:p="http://schemas.microsoft.com/3dmanufacturing/production/2015/06" requiredextensions="p"'
obj=io.StringIO()
obj.write(f'<?xml version="1.0" encoding="UTF-8"?>\n<model unit="millimeter" xml:lang="en-US" {NS}>\n <metadata name="BambuStudio:3mfVersion">1</metadata>\n <resources>\n')
for i,(nm,k,e,V,F) in enumerate(meshes): obj.write(mesh_xml(i+1,V,F))
obj.write(' </resources>\n <build/>\n</model>\n')
N=len(meshes); AID=N+1
comps='\n'.join(f'    <component p:path="/3D/Objects/object_1.model" objectid="{i+1}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 0 0 0"/>' for i in range(N))
TX=f'1 0 0 0 1 0 0 0 1 128 128 0'
main=f'''<?xml version="1.0" encoding="UTF-8"?>
<model unit="millimeter" xml:lang="en-US" {NS}>
 <metadata name="Application">BambuStudio-02.05.00.66</metadata>
 <metadata name="BambuStudio:3mfVersion">1</metadata>
 <metadata name="Title">CS2 Inferno - Bumbaru</metadata>
 <metadata name="Designer">Bumbaru (izvorni model: SiiGee, MakerWorld, BY-NC-SA)</metadata>
 <metadata name="License">BY-NC-SA</metadata>
 <resources>
  <object id="{AID}" p:UUID="{uuid.uuid4()}" type="model">
   <components>
{comps}
   </components>
  </object>
 </resources>
 <build p:UUID="{uuid.uuid4()}">
  <item objectid="{AID}" p:UUID="{uuid.uuid4()}" transform="{TX}" printable="1"/>
 </build>
</model>
'''
parts_xml=''
for i,(nm,k,e,V,F) in enumerate(meshes):
    parts_xml+=f'''    <part id="{i+1}" subtype="normal_part">
      <metadata key="name" value="{nm}"/>
      <metadata key="matrix" value="1 0 0 0 0 1 0 0 0 0 1 0 0 0 0 1"/>
      <metadata key="source_file" value="inferno.3mf"/>
      <metadata key="source_object_id" value="0"/>
      <metadata key="source_volume_id" value="{i}"/>
      <metadata key="extruder" value="{e}"/>
      <mesh_stat face_count="{len(F)}" edges_fixed="0" degenerate_facets="0" facets_removed="0" facets_reversed="0" backwards_edges="0"/>
    </part>
'''
ms=f'''<?xml version="1.0" encoding="UTF-8"?>
<config>
  <object id="{AID}">
    <metadata key="name" value="CS2 Inferno"/>
    <metadata key="extruder" value="1"/>
    <metadata face_count="{sum(len(m[4]) for m in meshes)}"/>
{parts_xml}  </object>
  <plate>
    <metadata key="plater_id" value="1"/>
    <metadata key="plater_name" value="Inferno"/>
    <metadata key="locked" value="false"/>
    <metadata key="filament_map_mode" value="Auto For Flush"/>
    <metadata key="filament_maps" value="1 1 1 1"/>
    <model_instance>
      <metadata key="object_id" value="{AID}"/>
      <metadata key="instance_id" value="0"/>
      <metadata key="identify_id" value="101"/>
    </model_instance>
  </plate>
  <assemble>
   <assemble_item object_id="{AID}" instance_id="0" transform="{TX}" offset="0 0 0" />
  </assemble>
</config>
'''
# project settings: extend from 3 to 4 filaments
P=json.load(open(W+'src/Metadata/project_settings.config'))
n=3
for k,v in list(P.items()):
    if not isinstance(v,list): continue
    if k in ('different_settings_to_system','inherits_group'):
        continue
    if len(v)==n and (k.startswith('filament') or k in ('default_filament_colour','nozzle_temperature_range_high','nozzle_temperature_range_low') or True) and k not in ('print_compatible_printers',):
        # per-filament list
        P[k]=v+[v[0]]
    elif len(v)==2*n and k!='flush_volumes_vector' and k!='upward_compatible_machine':
        P[k]=v+v[0:2]
    elif len(v)==4*n and k.startswith('filament_dev'):
        P[k]=v+v[0:4]
P['filament_self_index']=['1','1','2','2','3','3','4','4']
P['flush_volumes_vector']=['140']*8
P['flush_volumes_matrix']=[('0' if i==j else '280') for i in range(4) for j in range(4)]
P['filament_colour']=COLORS; P['filament_multi_colour']=COLORS
P['filament_colour_type']=['0']*4
P['filament_settings_id']=['Bambu PLA Basic @BBL P1S 0.4 nozzle']*4
P['filament_map']=['1']*4
P['default_filament_colour']=['']*4
dsts=P['different_settings_to_system']; P['different_settings_to_system']=[dsts[0],'','','','',dsts[-1]]
ig=P['inherits_group']; P['inherits_group']=[ig[0]]+['Bambu PLA Basic @BBL P1S 0.4 nozzle']*4+[ig[-1]]
bad=[k for k,v in P.items() if isinstance(v,list) and len(v)==3]
print('remaining len3 keys',bad)
ct='''<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="model" ContentType="application/vnd.ms-package.3dmanufacturing-3dmodel+xml"/>
 <Default Extension="png" ContentType="image/png"/>
 <Default Extension="gcode" ContentType="text/x.gcode"/>
</Types>'''
rels='''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/3dmodel.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
 <Relationship Target="/Metadata/plate_1.png" Id="rel-2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/thumbnail"/>
</Relationships>'''
mrels='''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/Objects/object_1.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>'''
from PIL import Image
th=Image.open(W+'work/prev.png'); th.thumbnail((512,512)); bt=io.BytesIO(); 
bg=Image.new('RGBA',(512,512),(0,0,0,0)); bg.paste(th,((512-th.size[0])//2,(512-th.size[1])//2)); bg.save(bt,'PNG')
with zipfile.ZipFile(OUT,'w',zipfile.ZIP_DEFLATED,compresslevel=6) as z:
    z.writestr('[Content_Types].xml',ct); z.writestr('_rels/.rels',rels)
    z.writestr('3D/3dmodel.model',main); z.writestr('3D/_rels/3dmodel.model.rels',mrels)
    z.writestr('3D/Objects/object_1.model',obj.getvalue())
    z.writestr('Metadata/model_settings.config',ms)
    z.writestr('Metadata/project_settings.config',json.dumps(P,indent=4))
    z.writestr('Metadata/slice_info.config','<?xml version="1.0" encoding="UTF-8"?>\n<config>\n  <header>\n    <header_item key="X-BBL-Client-Type" value="slicer"/>\n    <header_item key="X-BBL-Client-Version" value="02.05.00.66"/>\n  </header>\n</config>\n')
    z.writestr('Metadata/plate_1.png',bt.getvalue())
import os; print('written',OUT,os.path.getsize(OUT)/1e6,'MB')
# stats
for nm,k,e,V,F in meshes:
    t=trimesh.Trimesh(V,F); print(k,e,len(F),'vol cm3',round(t.volume/1000,2),'area mm2',round(t.area))
