import networkx as nx

G = nx.read_edgelist("edgeList.txt", create_using=nx.DiGraph())
page_rank = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)

result = ""
for id in page_rank:
    result += id + " " + str(page_rank[id]) + "\n"

with open("external_pageRankFile.txt", "w")as outfile:
    outfile.write(result)