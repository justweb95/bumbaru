import trimesh, numpy as np, zipfile, pickle, json, re, uuid, sys
P=pickle.load(open('parts.pkl','rb'))['ext']
order=['mapa','slovo_A','slovo_B','T_spawn','CT_spawn','kutije_A','kutije_B','kutije_topmid']
NICE={'mapa':'Mirage mapa (bez)','slovo_A':'Slovo A (crvena)','slovo_B':'Slovo B (crvena)','T_spawn':'T spawn mrezica (crvena)','CT_spawn':'CT spawn mrezica (plava)','kutije_A':'Kutije A sajt (braon)','kutije_B':'Kutije B sajt (braon)','kutije_topmid':'Kutije Top Mid (braon)'}
meshes=[trimesh.load(f'part_{n}.ply',process=False) for n in order]
allv=np.vstack([m.vertices for m in meshes]); c=(allv.min(0)+allv.max(0))/2; c[2]=0
NS='http://schemas.microsoft.com/3dmanufacturing/core/2015/02'
def mesh_xml(oid,m):
    v=m.vertices-c
    vs='\n'.join(f'     <vertex x="{x:.5f}" y="{y:.5f}" z="{z:.5f}"/>' for x,y,z in v)
    ts='\n'.join(f'     <triangle v1="{a}" v2="{b}" v3="{d}"/>' for a,b,d in m.faces)
    return f'  <object id="{oid}" p:UUID="{uuid.uuid4()}" type="model">\n   <mesh>\n    <vertices>\n{vs}\n    </vertices>\n    <triangles>\n{ts}\n    </triangles>\n   </mesh>\n  </object>\n'
objs=f'<?xml version="1.0" encoding="UTF-8"?>\n<model unit="millimeter" xml:lang="en-US" xmlns="{NS}" xmlns:BambuStudio="http://schemas.bambulab.com/package/2021" xmlns:p="http://schemas.microsoft.com/3dmanufacturing/production/2015/06" requiredextensions="p">\n <metadata name="BambuStudio:3mfVersion">1</metadata>\n <resources>\n'
for i,m in enumerate(meshes): objs+=mesh_xml(i+1,m)
objs+=' </resources>\n <build/>\n</model>\n'
AID=len(meshes)+1
comps='\n'.join(f'    <component p:path="/3D/Objects/object_1.model" objectid="{i+1}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 0 0 0"/>' for i in range(len(meshes)))
main=f'''<?xml version="1.0" encoding="UTF-8"?>
<model unit="millimeter" xml:lang="en-US" xmlns="{NS}" xmlns:BambuStudio="http://schemas.bambulab.com/package/2021" xmlns:p="http://schemas.microsoft.com/3dmanufacturing/production/2015/06" requiredextensions="p">
 <metadata name="Application">BambuStudio-02.07.01.57</metadata>
 <metadata name="BambuStudio:3mfVersion">1</metadata>
 <metadata name="Title">CS2 Mirage - Bumbaru</metadata>
 <resources>
  <object id="{AID}" p:UUID="{uuid.uuid4()}" type="model">
   <components>
{comps}
   </components>
  </object>
 </resources>
 <build p:UUID="{uuid.uuid4()}">
  <item objectid="{AID}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 128 128 0" printable="1"/>
 </build>
</model>
'''
parts=''
for i,(n,m) in enumerate(zip(order,meshes)):
    parts+=f'''    <part id="{i+1}" subtype="normal_part">
      <metadata key="name" value="{NICE[n]}"/>
      <metadata key="matrix" value="1 0 0 0 0 1 0 0 0 0 1 0 0 0 0 1"/>
      <metadata key="source_object_id" value="0"/>
      <metadata key="source_volume_id" value="{i}"/>
      <metadata key="extruder" value="{P[n]}"/>
      <mesh_stat face_count="{len(m.faces)}" edges_fixed="0" degenerate_facets="0" facets_removed="0" facets_reversed="0" backwards_edges="0"/>
    </part>
'''
cfg=f'''<?xml version="1.0" encoding="UTF-8"?>
<config>
  <object id="{AID}">
    <metadata key="name" value="CS2 Mirage"/>
    <metadata key="extruder" value="1"/>
    <metadata face_count="{sum(len(m.faces) for m in meshes)}"/>
{parts}  </object>
  <plate>
    <metadata key="plater_id" value="1"/>
    <metadata key="plater_name" value="mirage"/>
    <metadata key="locked" value="false"/>
    <model_instance>
      <metadata key="object_id" value="{AID}"/>
      <metadata key="instance_id" value="0"/>
      <metadata key="identify_id" value="{AID*10}"/>
    </model_instance>
  </plate>
  <assemble>
   <assemble_item object_id="{AID}" instance_id="0" transform="1 0 0 0 1 0 0 0 1 128 128 0" offset="0 0 0" />
  </assemble>
</config>
'''
src=sys.argv[1]
ps=json.loads(zipfile.ZipFile(src).read('Metadata/project_settings.config'))
ps['filament_colour']=['#E6CBA0','#C8102E','#1B2A5C','#6B4226']
ct='''<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="model" ContentType="application/vnd.ms-package.3dmanufacturing-3dmodel+xml"/>
 <Default Extension="png" ContentType="image/png"/>
</Types>
'''
rels='''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/3dmodel.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
 <Relationship Target="/Metadata/plate_1.png" Id="rel-2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/thumbnail"/>
</Relationships>
'''
mrels='''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/Objects/object_1.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>
'''
from PIL import Image
th=Image.open('preview.png'); bg=Image.new('RGBA',(512,512),(0,0,0,0)); th.thumbnail((500,500)); bg.paste(th,((512-th.size[0])//2,(512-th.size[1])//2)); bg.save('thumb.png')
out=sys.argv[2]
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
    z.writestr('[Content_Types].xml',ct); z.writestr('_rels/.rels',rels)
    z.writestr('3D/3dmodel.model',main); z.writestr('3D/_rels/3dmodel.model.rels',mrels)
    z.writestr('3D/Objects/object_1.model',objs)
    z.writestr('Metadata/model_settings.config',cfg)
    z.writestr('Metadata/project_settings.config',json.dumps(ps,indent=4))
    z.write('thumb.png','Metadata/plate_1.png')
print('ok',out)
