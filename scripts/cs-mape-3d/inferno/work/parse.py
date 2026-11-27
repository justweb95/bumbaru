import re, numpy as np, sys, time
src=sys.argv[1]; out=sys.argv[2]
t=time.time()
data=open(src,'rb').read()
# split objects
objs={}
for m in re.finditer(rb'<object id="(\d+)"',data):
    objs[int(m.group(1))]=m.start()
keys=sorted(objs,key=lambda k:objs[k])
res={}
for i,k in enumerate(keys):
    s=objs[k]; e=objs[keys[i+1]] if i+1<len(keys) else len(data)
    chunk=data[s:e]
    v=np.array(re.findall(rb'<vertex x="([^"]+)" y="([^"]+)" z="([^"]+)"',chunk),dtype=float)
    f=np.array(re.findall(rb'<triangle v1="(\d+)" v2="(\d+)" v3="(\d+)"',chunk),dtype=np.int64)
    print(k,v.shape,f.shape, v.min(0) if len(v) else None, v.max(0) if len(v) else None, flush=True)
    res[f'v{k}']=v; res[f'f{k}']=f
np.savez(out,**res)
print(time.time()-t)
