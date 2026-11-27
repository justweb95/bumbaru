import numpy as np, pickle, zipfile, json, uuid, io
from PIL import Image
P=pickle.load(open('parts.pkl','rb'))
out='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/3mf/dust2.3mf'
names=list(P.keys())
allv=np.concatenate([P[k][0] for k in names]); mn=allv.min(0); mx=allv.max(0)
ctr=(mn+mx)/2
objs=[]; comps=[]
for i,k in enumerate(names,1):
    v,f,e=P[k]; v=v-[ctr[0],ctr[1],mn[2]]
    vs='\n'.join(f'     <vertex x="{a:.5f}" y="{b:.5f}" z="{c:.5f}"/>' for a,b,c in v)
    ts='\n'.join(f'     <triangle v1="{a}" v2="{b}" v3="{c}"/>' for a,b,c in f)
    objs.append(f'  <object id="{i}" p:UUID="{uuid.uuid4()}" type="model">\n   <mesh>\n    <vertices>\n{vs}\n    </vertices>\n    <triangles>\n{ts}\n    </triangles>\n   </mesh>\n  </object>')
    comps.append(f'    <component p:path="/3D/Objects/object_1.model" objectid="{i}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 0 0 0"/>')
NS='xmlns="http://schemas.microsoft.com/3dmanufacturing/core/2015/02" xmlns:BambuStudio="http://schemas.bambulab.com/package/2021" xmlns:p="http://schemas.microsoft.com/3dmanufacturing/production/2015/06" requiredextensions="p"'
objmodel=f'<?xml version="1.0" encoding="UTF-8"?>\n<model unit="millimeter" xml:lang="en-US" {NS}>\n <metadata name="BambuStudio:3mfVersion">1</metadata>\n <resources>\n'+'\n'.join(objs)+'\n </resources>\n <build/>\n</model>\n'
OID=len(names)+1
main=f'''<?xml version="1.0" encoding="UTF-8"?>
<model unit="millimeter" xml:lang="en-US" {NS}>
 <metadata name="Application">BambuStudio-01.10.01.50</metadata>
 <metadata name="BambuStudio:3mfVersion">1</metadata>
 <metadata name="Title">Dust II - Bumbaru CS2 mapa</metadata>
 <resources>
  <object id="{OID}" p:UUID="{uuid.uuid4()}" type="model">
   <components>
{chr(10).join(comps)}
   </components>
  </object>
 </resources>
 <build p:UUID="{uuid.uuid4()}">
  <item objectid="{OID}" p:UUID="{uuid.uuid4()}" transform="1 0 0 0 1 0 0 0 1 128 128 0" printable="1"/>
 </build>
</model>
'''
pl=[]
for i,k in enumerate(names,1):
    v,f,e=P[k]
    pl.append(f'''    <part id="{i}" subtype="normal_part">
      <metadata key="name" value="{k}"/>
      <metadata key="matrix" value="1 0 0 0 0 1 0 0 0 0 1 0 0 0 0 1"/>
      <metadata key="extruder" value="{e}"/>
      <mesh_stat face_count="{len(f)}" edges_fixed="0" degenerate_facets="0" facets_removed="0" facets_reversed="0" backwards_edges="0"/>
    </part>''')
ms=f'''<?xml version="1.0" encoding="UTF-8"?>
<config>
  <object id="{OID}">
    <metadata key="name" value="dust2"/>
    <metadata key="extruder" value="1"/>
{chr(10).join(pl)}
  </object>
  <plate>
    <metadata key="plater_id" value="1"/>
    <metadata key="plater_name" value=""/>
    <metadata key="locked" value="false"/>
    <model_instance>
      <metadata key="object_id" value="{OID}"/>
      <metadata key="instance_id" value="0"/>
      <metadata key="identify_id" value="1"/>
    </model_instance>
  </plate>
  <assemble>
   <assemble_item object_id="{OID}" instance_id="0" transform="1 0 0 0 1 0 0 0 1 128 128 0" offset="0 0 0"/>
  </assemble>
</config>
'''
ps=json.load(open('src/Metadata/project_settings.config'))
skip={'printable_area','head_wrap_detect_zone'}
for k,v in list(ps.items()):
    if isinstance(v,list) and len(v)==4 and k not in skip: pass
ps['filament_colour']=['#E6CBA0','#C8102E','#1B2A5C','#2F5233']
for k,v in ps.items():
    if 'flush' in k: print('flush key',k,len(v) if isinstance(v,list) else v)
fm=ps.get('flush_volumes_matrix')
if False:
    M=[[fm[i*4+j] for j in range(4)] for i in range(4)]
    ps['flush_volumes_matrix']=[ (M[i][j] if i<4 and j<4 else ('0' if i==j else M[0][1])) for i in range(5) for j in range(5)]
fv=ps.get('flush_volumes_vector')
if False: ps['flush_volumes_vector']=fv+fv[:2]
src='src/'
thumb=io.BytesIO(); im=Image.open('prev.png'); im.thumbnail((512,512)); im.save(thumb,'PNG')
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
    z.write(src+'[Content_Types].xml','[Content_Types].xml')
    z.writestr('_rels/.rels','''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Target="/3D/3dmodel.model" Id="rel-1" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
 <Relationship Target="/Metadata/plate_1.png" Id="rel-2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/thumbnail"/>
</Relationships>''')
    z.writestr('3D/3dmodel.model',main)
    z.write(src+'3D/_rels/3dmodel.model.rels','3D/_rels/3dmodel.model.rels')
    z.writestr('3D/Objects/object_1.model',objmodel)
    z.writestr('Metadata/model_settings.config',ms)
    z.writestr('Metadata/project_settings.config',json.dumps(ps,indent=4))
    z.writestr('Metadata/plate_1.png',thumb.getvalue())
print('ok', mx-mn)
